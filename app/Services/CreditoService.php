<?php

namespace App\Services;

use App\Models\Credito;
use App\Models\Cliente;
use App\Models\CreditoInteres;
use App\Models\AbonoCredito;
use App\Models\AbonoDetalle;
use App\Models\CajaMovimiento;
use Illuminate\Support\Facades\DB;

class CreditoService
{
    /**
     * Recalcula el saldo pendiente real del crédito basado en el histórico.
     * Suma intereses 'aplicado' y resta montos aplicados en abonos 'Realizado'.
     */
    public function calcularSaldoReal(int $creditoId): float
    {
        $credito = Credito::select('monto_inicial')->findOrFail($creditoId);
        
        $totalIntereses = CreditoInteres::where('id_credito', $creditoId)
            ->where('estado', 'aplicado')
            ->sum('monto_interes');

        // Suma los montos aplicados desde la tabla de detalles vinculada a cabeceras realizadas
        $totalAbonos = AbonoDetalle::where('id_credito', $creditoId)
            ->whereHas('abono', function ($query) {
                $query->where('estado', 'Realizado');
            })
            ->sum('monto_aplicado_usd');

        return round(($credito->monto_inicial + $totalIntereses) - $totalAbonos, 2);
    }

    /**
     * Anula una indexación y recalcula el saldo.
     * Devuelve el monto que debería ser reembolsado si el saldo resultante es negativo.
     */
    public function anularIndexacion(int $interesId, string $observacion): array
    {
        return DB::transaction(function () use ($interesId, $observacion) {
            $interes = CreditoInteres::findOrFail($interesId);
            $credito = Credito::lockForUpdate()->findOrFail($interes->id_credito);

            if ($interes->estado === 'anulado') {
                throw new \Exception("Esta indexación ya fue anulada.");
            }

            $interes->update([
                'estado' => 'anulado',
                'observacion' => $interes->observacion . " | ANULADO: " . $observacion
            ]);

            $nuevoSaldo = $this->calcularSaldoReal($credito->id);
            $montoAReembolsar = 0;

            if ($nuevoSaldo < 0) {
                $montoAReembolsar = abs($nuevoSaldo);
                $credito->saldo_a_favor += $montoAReembolsar;
                $credito->saldo_pendiente = 0;
                $credito->estado = 'pagado'; 
            } else {
                $credito->saldo_pendiente = $nuevoSaldo;
                $credito->estado = ($nuevoSaldo <= 0) ? 'pagado' : 'pendiente';
            }

            $credito->save();

            return [
                'success' => true,
                'monto_a_reembolsar' => $montoAReembolsar,
                'nuevo_saldo' => $credito->saldo_pendiente,
                'saldo_a_favor' => $credito->saldo_a_favor
            ];
        });
    }

    /**
     * Procesa la aplicación o reembolso del saldo a favor de un cliente.
     */
    public function procesarGestionSaldo(int $creditoId, string $accion, array $datos): array
    {
        return DB::transaction(function () use ($creditoId, $accion, $datos) {
            if (!in_array($accion, ['aplicar', 'reembolso'], true)) {
                throw new \Exception('Acción no válida.');
            }

            $credito = Credito::lockForUpdate()->findOrFail($creditoId);
            $monto   = round((float) $credito->saldo_a_favor, 2);

            if ($monto <= 0) {
                return ['status' => 'error', 'success' => false, 'message' => 'No hay saldo disponible'];
            }

            $idCaja = $this->validarCajaOperable($datos['id_caja'] ?? null);

            if ($accion === 'aplicar') {
                $abonoCabecera = AbonoCredito::create([
                    'id_cliente'        => $credito->id_cliente,
                    'id_user'           => auth()->id(),
                    'id_caja'           => $idCaja,
                    'monto_total_usd'   => $monto,
                    'pago_usd_efectivo' => 0.00,
                    'pago_bs_efectivo'  => 0.00,
                    'pago_punto_bs'     => 0.00,
                    'pago_pagomovil_bs' => 0.00,
                    'detalles'          => 'Aplicación de saldo a favor: ' . ($datos['observacion'] ?? 'N/A'),
                    'estado'            => 'Realizado'
                ]);

                AbonoDetalle::create([
                    'id_abono'           => $abonoCabecera->id,
                    'id_credito'         => $credito->id,
                    'monto_aplicado_usd' => $monto,
                ]);

                $credito->saldo_pendiente = max(0, round($credito->saldo_pendiente - $monto, 2));
                if ($credito->saldo_pendiente <= 0) {
                    $credito->estado = 'pagado';
                }
            } else { // reembolso
                $forma = (string) ($datos['forma_salida'] ?? 'Efectivo USD');
                $esBs  = stripos($forma, 'bs') !== false;

                CajaMovimiento::create([
                    'id_caja'      => $idCaja,
                    'id_user'      => auth()->id(),
                    'id_credito'   => $credito->id,
                    'tipo'         => 'egreso',
                    'categoria'    => 'reembolso_saldo',
                    'efectivo_usd' => $esBs ? 0 : $monto,
                    'efectivo_bs'  => $esBs ? round($monto * (float) bcv_rate('USD'), 2) : 0,
                    'observacion'  => ($datos['referencia'] ?? 'Reembolso de saldo a favor') . ' (' . $forma . ')',
                ]);

                if ($credito->estado === 'anticipo') {
                    $credito->saldo_pendiente = 0;
                    $credito->estado = 'pagado';

                    $cliente = Cliente::lockForUpdate()->find($credito->id_cliente);
                    if ($cliente) {
                        $descontar = min((float) $cliente->saldo_a_favor, $monto);
                        if ($descontar > 0) {
                            $cliente->decrement('saldo_a_favor', $descontar);
                        }
                    }
                }
            }

            $credito->saldo_a_favor = 0;
            $credito->save();

            return ['status' => 'success', 'success' => true, 'message' => 'Operación realizada correctamente.'];
        });
    }

    private function validarCajaOperable($idCaja): int
    {
        if (!$idCaja) {
            throw new \Exception('No se indicó una caja para registrar la operación.');
        }

        $caja = DB::table('cajas')->where('id', $idCaja)->lockForUpdate()->first();

        if (!$caja) {
            throw new \Exception('No existe la caja indicada.');
        }

        $estado = strtolower(trim((string) ($caja->estado ?? '')));
        if ($estado !== 'abierta' || !empty($caja->fecha_cierre)) {
            throw new \Exception('La caja indicada no está abierta.');
        }

        $user = auth()->user();
        if (!$user->esAdmin() && !in_array((int) $caja->id_local, $user->idsLocalesActivos(), true)) {
            throw new \Exception('No tienes acceso al local de esta caja.');
        }

        return (int) $caja->id;
    }
}