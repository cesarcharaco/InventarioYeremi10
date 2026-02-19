<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Venta;
use App\Models\Local;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Gate;

class CajaController extends Controller
{
    /**
     * Vista de apertura de caja
     */
    public function create()
    {
        if (Gate::denies('operar-caja')) {
            return redirect()->back()->with('error', 'Acceso denegado.');
        }

        $user = Auth::user();
        
        // 1. Verificación de duplicidad
        $cajaAbierta = Caja::where('id_user', $user->id)->where('estado', 'abierta')->first();
        if ($cajaAbierta) {
            return redirect()->route('ventas.create')->with('info', 'Ya tienes una jornada activa.');
        }

        // 2. Lógica según el Rol
        if ($user->hasRole('admin')) { // Usando tu constante ROLE_SUPERADMIN
            $locales = Local::where('estado', 'activo')->get();
        } else {
            // Obtenemos el local desde la tabla pivote usando tu helper
            $miLocal = $user->localActual();
            
            if (!$miLocal) {
                return redirect()->route('home')->with('error', 'Tu usuario no tiene una sede activa asignada. Contacta al administrador.');
            }

            // Convertimos a colección para que la vista no rompa al usar @foreach o ->first()
            $locales = collect([$miLocal]);
        }
        
        return view('cajas.create', compact('locales'));
    }

    /**
     * Procesar la apertura
     */
    public function store(Request $request)
    {
        // 1. Doble validación de Seguridad
        if (Gate::denies('operar-caja')) {
            return redirect()->back()->with('error', 'No tienes permiso para abrir cajas.');
        }

        $user = Auth::user();

        // Si es vendedor, forzamos el ID de su local activo
        if (!$user->hasRole('admin')) {
            $local = $user->localActual();
            if($local) {
                $request->merge(['id_local' => $local->id]);
            }
        }

        $request->validate([
            'monto_apertura_usd' => 'required|numeric|min:0',
            'id_local' => 'required|exists:local,id'
        ]);

        Caja::create([
            'id_user' => $user->id,
            'id_local' => $request->id_local,
            'monto_apertura_usd' => $request->monto_apertura_usd,
            'fecha_apertura' => now(),
            'estado' => 'abierta'
        ]);

        return redirect()->route('ventas.create')->with('success', 'Caja abierta con éxito.');
    }

    /**
     * Vista de Cierre de Caja
     */
    public function edit($id)
    {
        $caja = Caja::findOrFail($id);
        // Seguridad: Solo el dueño o auditores
        if (Auth::id() !== $caja->id_user && Gate::denies('auditar-cajas')) {
            return redirect()->back()->with('error', 'Acceso denegado: No puedes cerrar una caja que no te pertenece.');
        }
        if ($caja->estado == 'cerrada') {
            return redirect()->route('home')->with('error', 'Esta caja ya fue cerrada.');
        }

        // CALCULAMOS EL "DEBERÍA HABER" (Basado en la tabla Ventas)
        $totales = Venta::where('id_caja', $caja->id)
            ->where('estado', 'completada')
            ->select(
                DB::raw('SUM(pago_usd_efectivo) as efectivo_usd'),
                DB::raw('SUM(pago_bs_efectivo) as efectivo_bs'),
                DB::raw('SUM(pago_punto_bs) as punto_bs'),
                DB::raw('SUM(pago_pagomovil_bs) as pagomovil_bs')
            )->first();

        // El esperado en efectivo USD incluye el monto de apertura
        $esperado_usd = ($totales->efectivo_usd ?? 0) + $caja->monto_apertura_usd;

        return view('cajas.edit', compact('caja', 'totales', 'esperado_usd'));
    }

    /**
     * Procesar el Cierre
     */
    public function update(Request $request, $id)
    {
        $caja = Caja::findOrFail($id);
        // Seguridad: Blindaje del proceso de guardado
        if (Auth::id() !== $caja->id_user && Gate::denies('auditar-cajas')) {
            return redirect()->back()->with('error', 'Acceso denegado: No tienes permiso para procesar este cierre.');
        }
        $request->validate([
            'reportado_usd_efectivo' => 'required|numeric',
            'reportado_bs_efectivo'  => 'required|numeric',
            'reportado_punto_bs'     => 'required|numeric',
            'reportado_pagomovil_bs' => 'required|numeric',
        ]);

        // Capturamos lo que el sistema dice antes de cerrar (Snapshot de seguridad)
        $totales = Venta::where('id_caja', $caja->id)
            ->where('estado', 'completada')
            ->select(
                DB::raw('SUM(pago_usd_efectivo) as efectivo_usd'),
                DB::raw('SUM(pago_bs_efectivo) as efectivo_bs'),
                DB::raw('SUM(pago_punto_bs) as punto_bs'),
                DB::raw('SUM(pago_pagomovil_bs) as pagomovil_bs')
            )->first();

        DB::transaction(function () use ($request, $caja, $totales) {
            $caja->update([
                // Lo que el vendedor contó
                'reportado_usd_efectivo' => $request->reportado_usd_efectivo,
                'reportado_bs_efectivo'  => $request->reportado_bs_efectivo,
                'reportado_punto_bs'     => $request->reportado_punto_bs,
                'reportado_pagomovil_bs' => $request->reportado_pagomovil_bs,

                // Lo que el sistema calculó (para la posteridad)
                'esperado_usd_efectivo' => ($totales->efectivo_usd ?? 0) + $caja->monto_apertura_usd,
                'esperado_bs_efectivo'  => $totales->efectivo_bs ?? 0,
                'esperado_punto_bs'     => $totales->punto_bs ?? 0,
                'esperado_pagomovil_bs' => $totales->pagomovil_bs ?? 0,

                'fecha_cierre' => Carbon::now(),
                'estado' => 'cerrada'
            ]);
        });

        return redirect()->route('home')->with('success', 'Caja cerrada y conciliada correctamente.');
    }
}
