<?php

namespace App\Services;

use App\Models\Credito;
use App\Models\CreditoInteres;
use App\Models\AbonoCredito;
use App\Models\CajaMovimiento;
use Illuminate\Support\Facades\DB;

class CreditoService
{
    /**
     * Recalcula el saldo pendiente real del crédito basado en el histórico.
     * Solo suma intereses 'aplicado' y resta abonos 'Realizado'.
     */
    public function calcularSaldoReal(int $creditoId): float
    {
        $credito = Credito::select('monto_inicial')->findOrFail($creditoId);
        
        $totalIntereses = CreditoInteres::where('id_credito', $creditoId)
            ->where('estado', 'aplicado')
            ->sum('monto_interes');

        $totalAbonos = AbonoCredito::where('id_credito', $creditoId)
            ->where('estado', 'Realizado')
            ->sum('monto_pagado_usd');

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

    public function procesarGestionSaldo(int $creditoId, string $accion, array $datos): array
    {
        return DB::transaction(function () use ($creditoId, $accion, $datos) {
            $credito = Credito::lockForUpdate()->findOrFail($creditoId);
            $monto = $credito->saldo_a_favor;

            if ($monto <= 0) return ['success' => false, 'message' => 'No hay saldo disponible'];

            if ($accion === 'aplicar') {
                AbonoCredito::create([
                    'id_credito' => $credito->id,
                    'monto_pagado_usd' => $monto,
                    'detalles' => 'Aplicación de saldo a favor: ' . ($datos['observacion'] ?? 'N/A'),
                    'estado' => 'Realizado'
                ]);
                $credito->saldo_pendiente -= $monto;
            } elseif ($accion === 'reembolso') {
                /*AQUÍ ES DONDE FALTA LA MAGIA:
                Debes insertar en tu tabla de movimientos de caja (egreso)*/
                CajaMovimiento::create([
                    'monto' => $monto,
                    'tipo' => 'egreso',
                    'metodo' => $datos['forma_salida'], // Viene del formulario
                    'detalles' => $datos['referencia'],
                    'id_user' => auth()->id()
                ]);
            }

            $credito->saldo_a_favor = 0;
            $credito->save();

            return ['success' => true];
        });
    }
}