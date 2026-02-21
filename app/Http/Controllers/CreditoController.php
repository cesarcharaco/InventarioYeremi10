<?php

namespace App\Http\Controllers;

use App\Models\Credito;
use App\Models\AbonoCredito;
use App\Models\Caja;
use App\Models\Local;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class CreditoController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Iniciamos la consulta con las relaciones necesarias
        $query = Credito::with(['cliente', 'venta'])
                 ->where('saldo_pendiente', '>', 0);

        // LÓGICA DE FILTRO POR ROL Y LOCAL
        // Si NO es admin, filtramos por los locales asignados al usuario
        if (!$user->esAdmin()) {
            // Obtenemos los IDs de los locales donde trabaja el encargado/vendedor
            $misLocales = $user->local()->pluck('local.id'); // Asumiendo relación en modelo User

            $query->whereHas('venta', function($q) use ($misLocales) {
                $q->whereIn('id_local', $misLocales);
            });
        }

        // Buscador por cliente
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

    public function anularAbono($id)
    {
        // 1. Verificar permiso usando la Gate que ya tienes
        if (Gate::denies('anular-abono')) {
            return redirect()->back()->with('error', 'No autorizado para anular abonos.');
        }

        try {
            DB::transaction(function () use ($id) {
                $abono = AbonoCredito::findOrFail($id);

                // Evitar doble anulación
                if ($abono->estado === 'Anulado') {
                    throw new \Exception('Este abono ya ha sido anulado anteriormente.');
                }

                $credito = Credito::findOrFail($abono->id_credito);

                // 2. REVERTIR: Devolvemos el monto al saldo pendiente del crédito
                $credito->saldo_pendiente += $abono->monto_pagado_usd;
                
                // 3. Si el crédito estaba "Pagado", vuelve a "Pendiente"
                if ($credito->saldo_pendiente > 0) {
                    $credito->estado = 'pendiente'; 
                }
                $credito->save();

                // 4. MARCAR ABONO COMO ANULADO
                $abono->update(['estado' => 'Anulado']);
            });

            return redirect()->back()->with('success', 'Abono anulado correctamente. La deuda del cliente ha sido actualizada.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al anular: ' . $e->getMessage());
        }
    }
}