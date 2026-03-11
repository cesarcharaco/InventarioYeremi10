<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\CajaMovimiento;
use App\Models\Local;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class MovimientoCajaController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Gate::denies('movimientos_caja')) {
                return redirect()->route('home')->with('error', 'No tienes permisos para gestionar movimientos de caja.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $movimientos = CajaMovimiento::with(['usuario', 'caja', 'credito'])
            ->latest()
            ->paginate(20);

        return view('movimientos.index', compact('movimientos'));
    }

    public function create()
    {
        try {
           

           
           $locales = Local::where('tipo', 'LOCAL')->get();

            if ($locales->isEmpty()) {
                return redirect()->route('movimientos.index')
                    ->with('error', 'No tienes locales asignados para registrar movimientos.');
            }

            return view('movimientos.create', compact('locales'));

        } catch (Exception $e) {
            return redirect()->route('movimientos.index')
                ->with('error', 'Error al cargar el formulario: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
            'tipo' => 'required|in:ingreso,egreso',
            'categoria' => 'required|string|max:100',
            'monto' => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|in:Efectivo Dólares,Efectivo Bolívares', // Validación estricta
            'observacion' => 'required|string|max:255',
            'id_local' => 'required|exists:locales,id'
        ]);

            $user = Auth::user();

            // LÓGICA DE CAJA MULTI-LOCAL
            $queryCaja = Caja::where('id_local', $request->id_local)
                             ->where('estado', 'abierta');

            if ($user->esAdmin()) {
                // Admin puede afectar cualquier caja abierta en ese local
                $caja = $queryCaja->first();
            } else {
                // Encargado/Usuario debe tener SU propia caja abierta
                $caja = $queryCaja->where('id_user', $user->id)->first();
            }

            if (!$caja) {
                $msj = $user->esAdmin() 
                    ? "No hay ninguna caja abierta en el local seleccionado." 
                    : "No tienes una caja abierta en este local para registrar el movimiento.";
                return back()->with('error', $msj)->withInput();
            }

            DB::transaction(function () use ($request, $caja, $user) {
                CajaMovimiento::create([
                    'id_caja' => $caja->id,
                    'id_user' => $user->id,
                    'tipo' => $request->tipo,
                    'categoria' => $request->categoria,
                    'monto' => $request->monto,
                    'metodo_pago' => $request->metodo_pago,
                    'observacion' => $request->observacion,
                ]);
            });

            return redirect()->route('movimientos.index')->with('success', 'Movimiento registrado exitosamente.');

        } catch (Exception $e) {
            return back()->with('error', 'Error al procesar el movimiento: ' . $e->getMessage())->withInput();
        }
    }
    public function edit($id)
    {
        try {
            $movimiento = CajaMovimiento::findOrFail($id);
            
            // Cargamos los locales solo para referencia visual o si decides permitir el cambio
            $locales = Local::where('id', $user->id_local)->where('tipo', 'LOCAL')->get(); 

            return view('movimientos.edit', compact('movimiento', 'locales'));

        } catch (Exception $e) {
            return redirect()->route('movimientos.index')
                ->with('error', 'No se pudo cargar el movimiento para editar.');
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $movimiento = CajaMovimiento::with('caja')->findOrFail($id);

            // 1. VALIDACIÓN DE SEGURIDAD: Solo editar si la caja está abierta
            if ($movimiento->caja->estado !== 'abierta') {
                return redirect()->route('movimientos.index')
                    ->with('error', 'No se puede editar este movimiento porque la caja asociada ya fue cerrada.');
            }

            // 2. VALIDACIÓN DE DATOS
            $request->validate([
                'categoria'   => 'required|string|max:100',
                'monto'       => 'required|numeric|min:0.01',
                'metodo_pago' => 'required|in:Efectivo Dólares,Efectivo Bolívares',
                'observacion' => 'required|string|max:255',
            ]);

            // 3. ACTUALIZACIÓN
            $movimiento->update([
                'categoria'   => $request->categoria,
                'monto'       => $request->monto,
                'metodo_pago' => $request->metodo_pago,
                'observacion' => $request->observacion,
            ]);

            return redirect()->route('movimientos.index')
                ->with('success', 'Movimiento actualizado correctamente.');

        } catch (Exception $e) {
            return back()->with('error', 'Ocurrió un error al intentar actualizar el registro.');
        }
    }

    public function show($id)
    {
        try {
            // Traemos el movimiento con sus relaciones para no tener que consultar varias veces
            $movimiento = CajaMovimiento::with(['caja.local', 'usuario', 'credito'])
                ->findOrFail($id);

            return view('movimientos.show', compact('movimiento'));

        } catch (Exception $e) {
            return redirect()->route('movimientos.index')
                ->with('error', 'No se pudo encontrar el detalle del movimiento.');
        }
    }

    public function destroy($id)
    {
        try {
            // Cargamos el movimiento incluyendo la caja relacionada
            $movimiento = CajaMovimiento::with('caja')->findOrFail($id);

            // 1. VALIDACIÓN DE SEGURIDAD: Solo borrar si la caja está abierta
            if ($movimiento->caja->estado !== 'abierta') {
                return redirect()->route('movimientos.index')
                    ->with('error', 'No se puede eliminar este registro porque la caja asociada ya fue cerrada.');
            }

            // 2. ELIMINACIÓN
            $movimiento->delete();

            return redirect()->route('movimientos.index')
                ->with('success', 'Movimiento eliminado correctamente.');

        } catch (Exception $e) {
            return back()->with('error', 'Error al intentar eliminar el registro.');
        }
    }
}