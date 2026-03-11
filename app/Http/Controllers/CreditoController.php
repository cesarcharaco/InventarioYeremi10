<?php

namespace App\Http\Controllers;

use App\Models\Credito;
use App\Models\AbonoCredito;
use App\Models\CreditoInteres;
use App\Models\Caja;
use App\Models\Cliente;
use App\Models\Local;
use App\Models\DetalleVenta;
use App\Services\CreditoService;
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

        // 1. Iniciamos la consulta desde el Cliente
        $query = Cliente::whereHas('creditos', function($q) {
            // Solo clientes que tengan créditos pendientes
            $q->where('estado', 'pendiente');
        })
        ->withSum(['creditos as saldo_total_pendiente' => function($q) {
            // La suma se hace a nivel de base de datos (muy rápido)
            $q->where('estado', 'pendiente');
        }], 'saldo_pendiente');

        // 2. Filtro de Locales (para encargados/vendedores)
        if (!$user->esAdmin()) {
            $misLocales = $user->local()->pluck('local.id');
            $query->whereHas('creditos.venta', function($q) use ($misLocales) {
                $q->whereIn('id_local', $misLocales);
            });
        }

        // 3. Buscador por cliente
        if ($request->filled('buscar')) {
            $query->where(function($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->buscar}%")
                  ->orWhere('identificacion', 'like', "%{$request->buscar}%");
            });
        }

        // Ejecutamos la paginación
        $clientes = $query->paginate(20);

        return view('creditos.index', compact('clientes'));
    }

    public function show($id)
    {
        // 1. Buscamos al cliente (el $id ahora representa al cliente)
        // Cargamos sus créditos pendientes y, de esos créditos, sus abonos e intereses
        $cliente = Cliente::with([
                'creditos' => function($q) {
                    //$q->where('estado', 'pendiente')
                      $q->with(['venta', 'abonos.usuario', 'intereses.administrador']);
                }
            ])->findOrFail($id);

        //dd($cliente);
        // 2. Aplanamos todos los abonos de todos los créditos para el historial global
        // Esto junta los abonos de la Factura A, B y C en una sola lista cronológica
        $historialAbonos = $cliente->creditos->flatMap(function($credito) {
            return $credito->abonos;
        })->sortByDesc('created_at');

        // 3. Preparamos el resumen financiero para la sección lateral (col-md-4)
        $resumen = [
            'monto_inicial'    => $cliente->creditos->sum('monto_inicial'),
            'total_intereses'  => $cliente->creditos->sum(function($c) { 
                return $c->intereses->sum('monto_interes'); 
            }),
            'saldo_pendiente'  => $cliente->creditos->sum('saldo_pendiente'),
            'saldo_a_favor'    => $cliente->creditos->sum('saldo_a_favor'),
        ];

        // Cálculos derivados
        $resumen['deuda_total']    = $resumen['monto_inicial'] + $resumen['total_intereses'];
        $resumen['total_abonado']  = $resumen['deuda_total'] - $resumen['saldo_pendiente'];

        // Consolidamos todos los intereses de todos los créditos del cliente
        $historialIntereses = $cliente->creditos->flatMap(function($credito) {
                return $credito->intereses;
            })->sortByDesc('aplicado_en');

        // 4. Retornamos la vista con los datos procesados
        return view('creditos.show', compact('cliente', 'historialAbonos', 'resumen','historialIntereses'));
    }

    public function registrarAbono(Request $request, $id)
    {
        // 1. Validaciones iniciales
        $request->validate(['monto_total_usd' => 'required|numeric|min:0.01']);
        
        // Validar desglose (mínimo un valor mayor a 0)
        $totalDesglose = ($request->pago_usd_efectivo ?? 0) + ($request->pago_bs_efectivo ?? 0) + 
                         ($request->pago_punto_bs ?? 0) + ($request->pago_pagomovil_bs ?? 0);

        if ($totalDesglose <= 0) return back()->with('error', 'Debe registrar al menos un valor en el desglose.');

        try {
            DB::transaction(function () use ($request, $id, $totalDesglose) {
                // El ID que llega es de un crédito, lo usamos para identificar al cliente
                $creditoReferencia = Credito::findOrFail($id);
                $cliente = $creditoReferencia->cliente;

                // 2. Buscamos TODOS los créditos pendientes de este cliente (Más viejos primero)
                $creditos = Credito::where('id_cliente', $cliente->id)
                    ->where('estado', 'pendiente')
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->get();

                $montoRestante = round($request->monto_total_usd, 2);

                foreach ($creditos as $credito) {
                    if ($montoRestante <= 0) break;

                    $saldo = round($credito->saldo_pendiente, 2);
                    $abono = min($montoRestante, $saldo);

                    // Registramos el abono para este crédito específico
                    AbonoCredito::create([
                        'id_credito' => $credito->id,
                        'id_user'    => Auth::id(),
                        'id_caja'    => $this->obtenerCajaActiva($credito), // Método auxiliar recomendado
                        'monto_pagado_usd' => $abono,
                        'detalles'   => 'Abono Global: ' . ($request->referencia ?? 'Sin referencia'),
                        'estado'     => 'Realizado'
                    ]);

                    // Actualizamos saldo
                    $credito->saldo_pendiente = round($saldo - $abono, 2);
                    if ($credito->saldo_pendiente <= 0) {
                        $credito->estado = 'pagado';
                        if($credito->venta) $credito->venta->update(['estado_pago' => 'Pagado']);
                    }
                    $credito->save();

                    $montoRestante -= $abono;
                }
            });

            return redirect()->back()->with('success', 'Abono procesado y distribuido correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar: ' . $e->getMessage());
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
            if (Gate::denies('anular-abono')) {
                return redirect()->back()->with('error', 'No autorizado para anular abonos.');
            }

            try {
                DB::transaction(function () use ($id) {
                    $abono = AbonoCredito::findOrFail($id);

                    if ($abono->estado === 'Anulado') {
                        throw new \Exception('Este abono ya ha sido anulado anteriormente.');
                    }

                    $abono->update(['estado' => 'Anulado']);

                    // RE-CALCULO: Usamos el servicio para asegurar consistencia
                    $service = new \App\Services\CreditoService();
                    $nuevoSaldo = $service->calcularSaldoReal($abono->id_credito);

                    $credito = Credito::findOrFail($abono->id_credito);
                    $credito->saldo_pendiente = $nuevoSaldo;
                    
                    $credito->estado = ($nuevoSaldo > 0) ? 'pendiente' : 'pagado';
                    $credito->save();
                });

                return redirect()->back()->with('success', 'Abono anulado correctamente. La deuda del cliente ha sido actualizada.');

            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Error al anular: ' . $e->getMessage());
            }
        }

    /**
     * Retorna la vista/modal para aplicar interés
     */
    public function modalInteres($id)
    {
        // Solo admins pueden indexar (puedes usar Gate si lo prefieres)
        if (!auth()->user()->esAdmin()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $credito = Credito::with('cliente')->findOrFail($id);
        
        // Retornamos una vista parcial para el modal
        return view('creditos.modals.modal_interes', compact('credito'))->render();
    }

    /**
     * Procesa la aplicación del interés moratorio
     */
    public function aplicarInteres(Request $request, $id)
    {
        $request->validate([
            'porcentaje' => 'required|numeric|min:0.01|max:100',
            'observacion' => 'nullable|string|max:255'
        ]);
        
        try {
            $res = DB::transaction(function () use ($request, $id) {
                $credito = Credito::lockForUpdate()->findOrFail($id);
                
                // 1. Cálculos previos
                $saldoAnterior = $credito->saldo_pendiente;
                $montoInteres = $saldoAnterior * ($request->porcentaje / 100);
                $saldoNuevo = $saldoAnterior + $montoInteres;

                // 2. Registro histórico (Agregando los campos faltantes)
                CreditoInteres::create([
                    'id_credito'    => $credito->id,
                    'id_user'       => Auth::id(),
                    'monto_interes' => $montoInteres,
                    'porcentaje'    => $request->porcentaje,
                    'saldo_anterior'=> $saldoAnterior, // Nuevo
                    'saldo_nuevo'   => $saldoNuevo,    // Nuevo
                    'aplicado_en'   => now(),          // Nuevo (timestamp actual)
                    'estado'        => 'aplicado',
                    'observacion'   => $request->observacion
                ]);

                // 3. Actualizar crédito
                $credito->saldo_pendiente = $saldoNuevo;
                $credito->save();

                return ['success' => true, 'mensaje' => "Interés aplicado exitosamente."];
            });

            return response()->json($res);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * Verifica la consistencia financiera del crédito
     */
    public function verificarAuditoria($id)
    {
        $credito = Credito::with(['abonos', 'intereses'])->findOrFail($id);
        
        // Cálculo: Monto Inicial + Total Intereses - Total Abonos
        $totalIntereses = $credito->total_intereses; // Usando el atributo del modelo
        $totalAbonos = $credito->abonos()->where('estado', '!=', 'Anulado')->sum('monto_pagado_usd');
        $saldoCalculado = ($credito->monto_inicial + $totalIntereses) - $totalAbonos;
        
        $esConsistente = abs($saldoCalculado - $credito->saldo_pendiente) < 0.01;

        return response()->json([
            'credito_id' => $credito->id,
            'consistente' => $esConsistente,
            'saldo_db' => $credito->saldo_pendiente,
            'saldo_calculado' => $saldoCalculado,
            'detalle' => $esConsistente ? "El saldo es correcto." : "¡Alerta! Descuadre detectado."
        ]);
    }

    public function anularInteres(Request $request, $id) 
    {
        $service = new CreditoService();
        $resultado = $service->anularIndexacion($id, $request->observacion);
        
        // Si necesitas avisar de un reembolso, puedes usar with('info', ...)
        $mensaje = 'Interés anulado correctamente.';
        if ($resultado['monto_a_reembolsar'] > 0) {
            $mensaje .= ' Nota: Se requiere un reembolso de $' . number_format($resultado['monto_a_reembolsar'], 2);
        }
        
        return redirect()->back()->with('success', $mensaje);
    }

    public function procesarGestionSaldo(int $creditoId, string $accion, array $datos)
    {
        return DB::transaction(function () use ($creditoId, $accion, $datos) {
            $credito = Credito::lockForUpdate()->findOrFail($creditoId);
            
            if ($accion === 'reembolso') {
                // 1. Aquí registrarías la salida en tu tabla de "MovimientosCaja" o "Egresos"
                // MovimientoCaja::create([...]);
                
                // 2. Limpiamos el saldo a favor
                $credito->saldo_a_favor = 0;
            } 
            // ... (lógica de 'aplicar' que ya definimos)
            
            $credito->save();
        });
    }

    public function gestionarSaldo(Request $request, $id)
    {
        $request->validate([
            'tipo_accion' => 'required|in:aplicar,reembolso',
            'referencia'  => 'required|string|max:255',
        ]);

        // Llamamos al servicio que contiene la lógica de negocio
        $service = new CreditoService();
        $resultado = $service->procesarGestionSaldo($id, $request->tipo_accion, $request->all());

        if ($resultado['success']) {
            return redirect()->back()->with('success', 'Operación realizada correctamente.');
        }

        return redirect()->back()->with('error', 'No se pudo completar la operación.');
    }

    private function obtenerCajaActiva()
    {
        $user = Auth::user();
        $local = $user->localActual();
        
        if (!$local) {
            return redirect()->route('home')->with('error', 'No tienes un local activo asignado.');
        }

        // Buscamos la caja abierta del local (Sin tocar sesiones)
        $caja = Caja::where('id_local', $local->id)
                    ->where('estado', 'abierta')
                    ->first();

        if (!$caja) {
            return redirect()->route('cajas.create')->with('error', 'No hay una caja abierta en este local.');
        }else{
            return $caja->id;
        }
    }

    public function listarProductos($id) 
    {
        $cliente = Cliente::findOrFail($id);
        
        // Obtenemos los detalles de venta directamente
        $detalles = DetalleVenta::whereHas('venta', function($q) use ($id) {
                $q->where('id_cliente', $id);
            })
            ->with(['venta', 'insumo.categoria', 'insumo.modeloVenta'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('creditos.productos', compact('cliente', 'detalles'));
    }
}