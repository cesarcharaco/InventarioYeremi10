<?php

namespace App\Http\Controllers;

use App\Models\Credito;
use App\Models\AbonoCredito;
use App\Models\Caja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CreditoController extends Controller
{
    public function index(Request $request)
    {
        $query = Credito::with(['cliente', 'venta'])
                 ->where('saldo_pendiente', '>', 0);

        if ($request->buscar) {
            $query->whereHas('cliente', function($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->buscar}%")
                  ->orWhere('identificacion', 'like', "%{$request->buscar}%");
            });
        }
        $creditos = $query->orderBy('fecha_vencimiento', 'asc')->get();
        
        return view('creditos.index', compact('creditos'));
    }

    public function show($id)
    {
        // Esta función cubre también la ruta 'creditos.historial' 
        // ya que cargamos los abonos aquí mismo.
        $credito = Credito::with(['cliente', 'venta', 'abonos.usuario'])->findOrFail($id);
        return view('creditos.show', compact('credito'));
    }

    public function registrarAbono(Request $request, $id)
    {
        if (Gate::denies('registrar-abono')) {
            return redirect()->back()->with('error', 'No tienes permiso para registrar abonos.');
        
    }
        $request->validate([
            'monto_total_usd' => 'required|numeric|min:0.01',
        ]);

        $caja = Caja::where('id_user', Auth::id())->where('estado', 'abierta')->first();
        if (!$caja) {
            return back()->with('error', 'Debes tener una caja abierta.');
        }

        try {
            DB::transaction(function () use ($request, $caja, $id) {
                $credito = Credito::lockForUpdate()->findOrFail($id);

                if ($request->monto_total_usd > $credito->saldo_pendiente) {
                    throw new \Exception("El abono excede el saldo.");
                }

                AbonoCredito::create([
                    'id_credito'        => $credito->id,
                    'id_user'           => Auth::id(),
                    'id_caja'           => $caja->id,
                    'monto_pagado_usd'  => $request->monto_total_usd,
                    'pago_usd_efectivo' => $request->pago_usd_efectivo ?? 0,
                    'pago_bs_efectivo'  => $request->pago_bs_efectivo ?? 0,
                    'pago_punto_bs'     => $request->pago_punto_bs ?? 0,
                    'pago_pagomovil_bs' => $request->pago_pagomovil_bs ?? 0,
                    'detalles'          => $request->referencia,
                ]);

                $credito->saldo_pendiente -= $request->monto_total_usd;
                if ($credito->saldo_pendiente <= 0) {
                    $credito->saldo_pendiente = 0;
                    $credito->estado = 'pagado';
                }
                $credito->save();
            });

            return redirect()->route('creditos.index')->with('success', 'Abono registrado.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Revalorizar: Ajusta la deuda si la tasa de cambio subió 
     * (Opcional según tu modelo de negocio)
     */
    public function revalorizar(Request $request, $id)
    {
        $credito = Credito::findOrFail($id);
        // Aquí iría tu lógica para multiplicar saldo_pendiente por nueva tasa
        // Por ahora lo dejamos como stub para que la ruta no de error 404
        return back()->with('info', 'Función de revalorización en desarrollo.');
    }
}