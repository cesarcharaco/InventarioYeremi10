<?php
namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\User;
use App\Models\Venta;
use App\Models\AbonoCredito;
use App\Models\Local;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CajaController extends Controller
{
    public function index(Request $request) // Añadido el $request que faltaba
    {
        if (Gate::denies('auditar-cajas')) {
            return redirect()->back()->with('error', 'Acceso denegado.');
        }

        $query = Caja::with(['user', 'local']);
        
        if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
            $desde = Carbon::parse($request->fecha_desde)->startOfDay();
            $hasta = Carbon::parse($request->fecha_hasta)->endOfDay();
            $query->whereBetween('fecha_apertura', [$desde, $hasta]);
        }

        $cajas = $query->orderBy('id', 'desc')->get();
        return view('cajas.index', compact('cajas'));
    }

    // ... dentro de CajaController

    public function create()
{
    if (Gate::denies('operar-caja')) {
        return redirect()->back()->with('error', 'Acceso denegado.');
    }

    $user = Auth::user();
    
    // 1. Buscamos si ya existe una caja abierta en el local del usuario
    // Si es admin, id_local_busqueda será null y traerá la primera que encuentre (o puedes ajustar esto si el admin debe ver todas)
    $id_local_busqueda = ($user->role === 'admin') ? null : $user->localActual()->id;

    $queryCaja = Caja::where('estado', 'abierta');
    if ($id_local_busqueda) {
        $queryCaja->where('id_local', $id_local_busqueda);
    }
    $cajaAbierta = $queryCaja->first();

    // Si hay caja abierta, mandamos a la vista de cierre
    if ($cajaAbierta) {
        $arqueo = $this->calcularTotalesCaja($cajaAbierta);
        return view('cajas.close', [
            'cajaAbierta' => $cajaAbierta,
            'totales' => (object)$arqueo['totales'],
            'esperado_usd' => $arqueo['esperado_usd_efectivo'],
            'esperado_bs'  => $arqueo['esperado_bs_efectivo']
        ]);
    }

    // 2. Lógica de selección para apertura nueva
    if ($user->role === 'admin') { 
        // Admin: Solo locales tipo 'LOCAL' y usuarios con rol 'vendedor'
        $locales = Local::where('estado', 'Activo')
                        ->where('tipo', 'LOCAL') 
                        ->get();
        
        $vendedores = User::where('role', 'vendedor')
                          ->where('activo', true)
                          ->get();

    } elseif ($user->role === 'encargado') {
        $miLocal = $user->localActual();
        
        // El encargado solo ve su local si es tipo 'LOCAL'
        $locales = ($miLocal && $miLocal->tipo === 'LOCAL') ? collect([$miLocal]) : collect([]);
        
        // Vendedores asociados a ese local específico
        $vendedores = User::whereHas('local', function($q) use ($miLocal) {
            $q->where('id_local', $miLocal->id);
        })->where('role', 'vendedor')
          ->where('activo', true)
          ->get();

    } else {
        // Vendedor: Solo su propio local si es tipo 'LOCAL'
        $miLocal = $user->localActual();
        $locales = ($miLocal && $miLocal->tipo === 'LOCAL') ? collect([$miLocal]) : collect([]);
        $vendedores = collect([$user]);
    }
    
    // Validación de seguridad: Si no hay local tipo 'LOCAL', no se puede abrir caja
    if ($locales->isEmpty()) {
        return redirect()->route('home')->with('error', 'Su ubicación actual no permite apertura de cajas (Área de Depósito/Administración).');
    }

    return view('cajas.create', compact('locales', 'vendedores'));
}

    public function store(Request $request)
    {
        if (Gate::denies('operar-caja')) {
            return redirect()->back()->with('error', 'Permiso denegado.');
        }

        $user = Auth::user();

        // Validaciones ajustadas para incluir al responsable elegido
        $request->validate([
            'id_local' => 'required|exists:local,id',
            'id_user'  => 'required|exists:users,id', // El responsable elegido
            'monto_apertura_usd' => 'required|numeric|min:0',
            'monto_apertura_bs'  => 'required|numeric|min:0',
        ]);

        // Verificación de seguridad: No abrir dos cajas en el mismo local
        $existe = Caja::where('id_local', $request->id_local)->where('estado', 'abierta')->exists();
        if($existe) return redirect()->back()->with('error', 'Ya existe una caja abierta para este local.');

        Caja::create([
            'id_user'  => $request->id_user, // Responsable
            'id_aperturador' => $request->id_user,   // Quién la abrió (Asegúrate de tener este campo en la tabla/modelo)
            'id_local' => $request->id_local,
            'monto_apertura_usd' => $request->monto_apertura_usd,
            'monto_apertura_bs'  => $request->monto_apertura_bs,
            'fecha_apertura' => now(),
            'estado' => 'abierta'
        ]);

        return redirect()->route('ventas.create')->with('success', 'Caja abierta correctamente.');
    }

    public function edit($id)
    {
        $caja = Caja::findOrFail($id);
        
        if (Auth::id() !== $caja->id_user && Gate::denies('auditar-cajas')) {
            return redirect()->back()->with('error', 'No puedes gestionar esta caja.');
        }

        if ($caja->estado == 'cerrada') {
            return redirect()->route('cajas.index')->with('error', 'Esta caja ya está cerrada.');
        }

        // --- CÁLCULO PROFESIONAL DE ARQUEO (Ventas + Abonos) ---
        $arqueo = $this->calcularTotalesCaja($caja);

        return view('cajas.edit', [
            'caja' => $caja,
            'totales' => $arqueo['totales'],
            'esperado_usd' => $arqueo['esperado_usd_efectivo'],
            'esperado_bs'  => $arqueo['esperado_bs_efectivo']
        ]);
    }

    public function update(Request $request, $id)
    {
        $caja = Caja::findOrFail($id);

        $request->validate([
            'reportado_usd_efectivo' => 'required|numeric|min:0|max:999999',
            'reportado_bs_efectivo'  => 'required|numeric|min:0|max:999999',
            'reportado_punto_bs'     => 'required|numeric|min:0|max:999999',
            'reportado_biopago_bs'   => 'required|numeric|min:0|max:999999',
        ]);

        $arqueo = $this->calcularTotalesCaja($caja);

        DB::transaction(function () use ($request, $caja, $arqueo) {
            $caja->update([
                // CORRECCIÓN: Nombres según tu $fillable del modelo
                'reportado_cierre_usd_efectivo' => $request->reportado_usd_efectivo,
                'reportado_cierre_bs_efectivo'  => $request->reportado_bs_efectivo,
                'reportado_cierre_punto'        => $request->reportado_punto_bs,
                // Nota: Tu modelo no tiene 'reportado_cierre_pagomovil' en el fillable, 
                // pero si lo tienes en la tabla, deberías agregarlo al modelo.
                
                // Snapshot del sistema
                'monto_cierre_usd_efectivo' => $arqueo['esperado_usd_efectivo'],
                'monto_cierre_bs_efectivo'  => $arqueo['esperado_bs_efectivo'],
                'monto_cierre_punto'        => $arqueo['totales']['punto_bs'],
                'monto_cierre_pagomovil'    => $arqueo['totales']['pagomovil_bs'],

                'fecha_cierre' => now(),
                'estado' => 'cerrada'
            ]);
        });

        return redirect()->route('cajas.index')->with('success', 'Caja cerrada y conciliada.');
    }

    /**
     * Helper privado para centralizar el cálculo de arqueo
     * Evita duplicar lógica en edit y update
     */
    private function calcularTotalesCaja($caja)
    {
        // 1. Sumamos los pagos de VENTAS realizadas con esta caja abierta
        $ventas = Venta::where('id_caja', $caja->id)
            ->where('estado', 'completada')
            ->select(
                DB::raw('SUM(pago_usd_efectivo) as usd_e'),
                DB::raw('SUM(pago_bs_efectivo) as bs_e'),
                DB::raw('SUM(pago_punto_bs) as bs_p'),
                DB::raw('SUM(pago_pagomovil_bs) as bs_pm')
            )->first();

        // 2. Sumamos los ABONOS de créditos recibidos con esta caja abierta
        $abonos = AbonoCredito::where('id_caja', $caja->id)
            ->where('estado', 'Realizado')
            ->select(
                DB::raw('SUM(pago_usd_efectivo) as usd_e'),
                DB::raw('SUM(pago_bs_efectivo) as bs_e'),
                DB::raw('SUM(pago_punto_bs) as bs_p'),
                DB::raw('SUM(pago_pagomovil_bs) as bs_pm')
            )->first();

        // 3. Consolidación de totales por método de pago
        $totales = [
            // Efectivo ingresado (Ventas + Abonos)
            'efectivo_usd_ingreso' => ($ventas->usd_e ?? 0) + ($abonos->usd_e ?? 0),
            'efectivo_bs_ingreso'  => ($ventas->bs_e ?? 0) + ($abonos->bs_e ?? 0),
            
            // Dinero digital (Punto de venta y Biopago suelen ir a la misma cuenta)
            'punto_bs'     => ($ventas->bs_p ?? 0) + ($ventas->bs_bio ?? 0) + ($abonos->bs_p ?? 0),
            
            // Pagomovil y Transferencias
            'pagomovil_bs' => ($ventas->bs_pm ?? 0) + ($ventas->bs_tr ?? 0) + ($abonos->bs_pm ?? 0),
        ];

        // 4. Cálculo del ESPERADO (Apertura + Ingresos)
        return [
            'totales' => $totales,
            'esperado_usd_efectivo' => $totales['efectivo_usd_ingreso'] + $caja->monto_apertura_usd,
            'esperado_bs_efectivo'  => $totales['efectivo_bs_ingreso'] + $caja->monto_apertura_bs
        ];
    }

    public function anular($id)
    {
        if (Gate::denies('auditar-cajas')) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        try {
            $caja = Caja::findOrFail($id);
            
            if ($caja->estado === 'anulada') {
                return response()->json(['success' => false, 'message' => 'La caja ya estaba anulada.']);
            }

            $caja->update(['estado' => 'anulada']);

            // Respondemos al AJAX
            return response()->json([
                'success' => true, 
                'message' => 'La jornada ha sido anulada correctamente.'
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al anular: ' . $e->getMessage()], 500);
        }
    }

    public function getVendedoresPorLocal($id)
    {
        // Buscamos los usuarios que tienen este local asociado y tienen rol vendedor
        $vendedores = User::whereHas('local', function($q) use ($id) {
            $q->where('id_local', $id);
        })->where('role', 'vendedor')
          ->where('activo', true)
          ->get(['id', 'name']); // Solo traemos lo necesario

        return response()->json($vendedores);
    }
}