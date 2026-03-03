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
    $miLocal = $user->localActual(); 
    
    // 1. Buscamos si ya existe una caja abierta
    $queryCaja = Caja::where('estado', 'abierta');
    
    if ($user->role !== 'admin') {
        if (!$miLocal) {
            return redirect()->route('home')->with('error', 'No tiene un local asignado para operar.');
        }
        $queryCaja->where('id_local', $miLocal->id);
    } else {
        if ($miLocal) {
            $queryCaja->where('id_local', $miLocal->id);
        }
    }

    $cajaAbierta = $queryCaja->first();

    // --- SI HAY CAJA ABIERTA: Lógica de Cierre ---
    if ($cajaAbierta) {
        $arqueo = $this->calcularTotalesCaja($cajaAbierta);
        
        return view('cajas.close', [
            'cajaAbierta'  => $cajaAbierta,
            'totales'      => (object)$arqueo['totales'],
            'esperado_usd' => $arqueo['esperado_usd_efectivo'],
            'esperado_bs'  => $arqueo['esperado_bs_efectivo'],
            // CORRECCIÓN: 'metodos_electronicos' es el nombre que definimos en calcularTotalesCaja
            'otros_pagos'  => (object)$arqueo['metodos_electronicos'] 
        ]);
    }

    // --- SI NO HAY CAJA ABIERTA: Lógica de Apertura ---
    $locales = collect([]);
    $vendedores = collect([]);

    if ($user->role === 'admin') { 
        $locales = Local::where('estado', 'Activo')->where('tipo', 'LOCAL')->get();
        $vendedores = User::where('role', 'vendedor')->where('activo', true)->get();

    } elseif ($user->role === 'encargado') {
        if ($miLocal && $miLocal->tipo === 'LOCAL') {
            $locales = collect([$miLocal]);
            // Ajustado a la relación id_local de tu DB
            $vendedores = User::where('id_local', $miLocal->id) 
                              ->where('role', 'vendedor')
                              ->where('activo', true)
                              ->get();
        }
    } else {
        // Vendedor: Solo su propio local y él mismo
        if ($miLocal && $miLocal->tipo === 'LOCAL') {
            $locales = collect([$miLocal]);
            $vendedores = collect([$user]);
        }
    }
    
    if ($locales->isEmpty()) {
        return redirect()->route('home')->with('error', 'Su ubicación o rol actual no permite la apertura de cajas.');
    }

    return view('cajas.create', compact('locales', 'vendedores'));
}
   public function store(Request $request)
{
    if (Gate::denies('operar-caja')) {
        return redirect()->back()->with('error', 'Permiso denegado.');
    }

    // 1. Validaciones
    $request->validate([
        'id_local' => 'required|exists:local,id',
        'id_user'  => 'required|exists:users,id', // Responsable de la caja
        'monto_apertura_usd' => 'required|numeric|min:0',
        'monto_apertura_bs'  => 'required|numeric|min:0',
    ]);

    // 2. Verificación de seguridad: No abrir dos cajas en el mismo local
    $existe = Caja::where('id_local', $request->id_local)
                  ->where('estado', 'abierta')
                  ->exists();
                  
    if($existe) {
        return redirect()->back()->with('error', 'Ya existe una caja abierta para este local.');
    }

    // 3. Creación de la Caja
    // Nota: Eliminé 'id_aperturador' porque no está en tu migración. 
    // Usamos 'id_user' como el responsable principal de los fondos.
    Caja::create([
        'id_user'            => $request->id_user, 
        'id_local'           => $request->id_local,
        'monto_apertura_usd' => $request->monto_apertura_usd,
        'monto_apertura_bs'  => $request->monto_apertura_bs,
        'fecha_apertura'     => now(),
        'estado'             => 'abierta'
    ]);

    // Importante: Redirigimos a ventas.create porque al abrir caja, 
    // el sistema ya debe permitirle facturar.
    return redirect()->route('ventas.create')->with('success', 'Caja abierta correctamente. Ya puede procesar ventas.');
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

    // --- CÁLCULO DE ARQUEO INCLUYENDO ELECTRÓNICOS ---
    $arqueo = $this->calcularTotalesCaja($caja);

    return view('cajas.edit', [
        'caja' => $caja,
        'totales' => $arqueo['totales'],
        'esperado_usd' => $arqueo['esperado_usd_efectivo'], // Solo físico
        'esperado_bs'  => $arqueo['esperado_bs_efectivo'],  // Solo físico
        'metodos_electronicos' => $arqueo['metodos_electronicos'] // Zelle, Punto, PM
    ]);
}

    public function update(Request $request, $id)
{
    $caja = Caja::findOrFail($id);

    // 1. Validación: Solo validamos lo que el vendedor puede contar físicamente
    $request->validate([
        'reportado_usd_efectivo' => 'required|numeric|min:0',
        'reportado_bs_efectivo'  => 'required|numeric|min:0',
        'reportado_punto_bs'     => 'required|numeric|min:0', // Suma de Punto + Biopago del vendedor
    ]);

    // 2. Ejecutamos el arqueo del sistema para comparar
    $arqueo = $this->calcularTotalesCaja($caja);

    DB::transaction(function () use ($request, $caja, $arqueo) {
        $caja->update([
            // --- LO QUE EL VENDEDOR REPORTÓ (FÍSICO) ---
            'reportado_cierre_usd_efectivo' => $request->reportado_usd_efectivo,
            'reportado_cierre_bs_efectivo'  => $request->reportado_bs_efectivo,
            'reportado_cierre_punto'        => $request->reportado_punto_bs,

            // --- LO QUE EL SISTEMA DICE QUE DEBE HABER (SISTEMA) ---
            // Usamos los nombres exactos de tu migración
            'monto_cierre_usd_efectivo' => $arqueo['esperado_usd_efectivo'],
            'monto_cierre_bs_efectivo'  => $arqueo['esperado_bs_efectivo'],
            'monto_cierre_punto'        => $arqueo['metodos_electronicos']['punto'],
            'monto_cierre_pagomovil'    => $arqueo['metodos_electronicos']['pagomovil'],
            
            // Nota: Aunque el vendedor no reporte Zelle, el sistema guarda el monto 
            // de Zelle en una tabla aparte o podrías añadir 'monto_cierre_zelle' si amplias la tabla.

            'fecha_cierre' => now(),
            'estado' => 'cerrada'
        ]);
    });

    return redirect()->route('cajas.index')->with('success', 'Caja cerrada y conciliada exitosamente.');
}

    /**
     * Helper privado para centralizar el cálculo de arqueo
     * Evita duplicar lógica en edit y update
     */
    private function calcularTotalesCaja($caja)
{
    // 1. Sumamos EFECTIVO desde VENTAS y ABONOS
    $efectivoVentas = Venta::where('id_caja', $caja->id)
        ->where('estado', 'completada')
        ->select(
            DB::raw('SUM(pago_usd_efectivo) as usd_e'),
            DB::raw('SUM(pago_bs_efectivo) as bs_e')
        )->first();

    $efectivoAbonos = AbonoCredito::where('id_caja', $caja->id)
        ->where('estado', 'Realizado')
        ->select(
            DB::raw('SUM(pago_usd_efectivo) as usd_e'),
            DB::raw('SUM(pago_bs_efectivo) as bs_e')
        )->first();

    // 2. Sumamos PAGOS ELECTRÓNICOS desde la tabla de REFERENCIAS
    // Buscamos todas las referencias asociadas a las ventas de esta caja
    $idsVentas = Venta::where('id_caja', $caja->id)->where('estado', 'completada')->pluck('id');
    
    $referencias = DB::table('pago_referencias')
        ->whereIn('id_venta', $idsVentas)
        ->select('metodo', DB::raw('SUM(monto_bs) as total_bs'), DB::raw('SUM(monto_usd) as total_usd'))
        ->groupBy('metodo')
        ->get();

    // 3. Consolidación de totales
    $totales = [
        'efectivo_usd_ingreso' => ($efectivoVentas->usd_e ?? 0) + ($efectivoAbonos->usd_e ?? 0),
        'efectivo_bs_ingreso'  => ($efectivoVentas->bs_e ?? 0) + ($efectivoAbonos->bs_e ?? 0),
        
        // Extraemos del grupo de referencias
        'punto_bs'     => $referencias->where('metodo', 'Punto')->first()->total_bs ?? 0,
        'pagomovil_bs' => $referencias->where('metodo', 'Pago Movil')->first()->total_bs ?? 0,
        'zelle_usd'    => $referencias->where('metodo', 'Zelle')->first()->total_usd ?? 0,
    ];

    // 4. Cálculo del ESPERADO (Apertura + Ingresos en efectivo)
    return [
        'totales' => $totales,
        'metodos_electronicos' => [
            'punto' => $totales['punto_bs'],
            'pagomovil' => $totales['pagomovil_bs'],
            'zelle' => $totales['zelle_usd']
        ],
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