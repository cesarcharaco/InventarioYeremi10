<?php
namespace App\Http\Controllers;

use App\Models\Caja;
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

    public function create()
    {
        if (Gate::denies('operar-caja')) {
            return redirect()->back()->with('error', 'Acceso denegado.');
        }

        $user = Auth::user();
        // Comprobar si ya tiene una caja abierta
        $cajaAbierta = Caja::where('id_user', $user->id)->where('estado', 'abierta')->first();

        if ($cajaAbierta) {
            // USAMOS EL HELPER PROFESIONAL QUE SUMA TODO
            $arqueo = $this->calcularTotalesCaja($cajaAbierta);

            return view('cajas.close', [
                'cajaAbierta' => $cajaAbierta,
                'totales' => (object)$arqueo['totales'], // Lo pasamos como objeto para tu vista
                'esperado_usd' => $arqueo['esperado_usd_efectivo'],
                'esperado_bs'  => $arqueo['esperado_bs_efectivo']
            ]);
        }

        // Selección de locales según rol
        if ($user->role === 'admin') { 
            $locales = Local::where('estado', 'activo')->get();
        } else {
            $miLocal = $user->localActual(); // Tu helper de la tabla pivote
            if (!$miLocal) {
                return redirect()->route('home')->with('error', 'No tienes sede asignada.');
            }
            $locales = collect([$miLocal]);
        }
        
        return view('cajas.create', compact('locales'));
    }

    public function store(Request $request)
    {
        if (Gate::denies('operar-caja')) {
            return redirect()->back()->with('error', 'Permiso denegado.');
        }

        $user = Auth::user();

        if ($user->role !== 'admin') {
            $local = $user->localActual();
            if ($local) $request->merge(['id_local' => $local->id]);
        }

        $request->validate([
            'id_local' => 'required|exists:local,id',
            'monto_apertura_usd' => 'required|numeric|min:0|max:999999',
            'monto_apertura_bs'  => 'required|numeric|min:0|max:999999',
        ]);

        Caja::create([
            'id_user' => $user->id,
            'id_local' => $request->id_local,
            'monto_apertura_usd' => $request->monto_apertura_usd,
            'monto_apertura_bs'  => $request->monto_apertura_bs,
            'fecha_apertura' => now(),
            'estado' => 'abierta'
        ]);

        return redirect()->route('ventas.create')->with('success', 'Caja abierta. ¡Buenas ventas!');
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
        $ventas = Venta::where('id_caja', $caja->id)->where('estado', 'completada')
            ->select(
                DB::raw('SUM(pago_usd_efectivo) as usd_e'),
                DB::raw('SUM(pago_bs_efectivo) as bs_e'),
                DB::raw('SUM(pago_punto_bs) as bs_p'),
                DB::raw('SUM(pago_biopago_bs) as bs_bio'),
                DB::raw('SUM(pago_pagomovil_bs) as bs_pm'),
                DB::raw('SUM(pago_transferencia_bs) as bs_tr')
            )->first();

        $abonos = AbonoCredito::where('id_caja', $caja->id)
            ->select(
                DB::raw('SUM(pago_usd_efectivo) as usd_e'),
                DB::raw('SUM(pago_bs_efectivo) as bs_e'),
                DB::raw('SUM(pago_punto_bs) as bs_p'),
                DB::raw('SUM(pago_pagomovil_bs) as bs_pm')
            )->first();

        $totales = [
            'efectivo_usd' => ($ventas->usd_e ?? 0) + ($abonos->usd_e ?? 0),
            'efectivo_bs'  => ($ventas->bs_e ?? 0) + ($abonos->bs_e ?? 0),
            'punto_bs'     => ($ventas->bs_p ?? 0) + ($ventas->bs_bio ?? 0) + ($abonos->bs_p ?? 0),
            'pagomovil_bs' => ($ventas->bs_pm ?? 0) + ($ventas->bs_tr ?? 0) + ($abonos->bs_pm ?? 0),
        ];

        return [
            'totales' => $totales,
            'esperado_usd_efectivo' => $totales['efectivo_usd'] + $caja->monto_apertura_usd,
            'esperado_bs_efectivo'  => $totales['efectivo_bs'] + $caja->monto_apertura_bs
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
}