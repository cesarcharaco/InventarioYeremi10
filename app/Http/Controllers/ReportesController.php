<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Insumos;
use App\Models\Incidencias;
use App\Models\HistorialIncidencias;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportesController extends Controller
{
    public function index()
    {
        $fecha_actual = Carbon::today()->format('Y-m-d');

        $in_almacen = $out_almacen = $disponibles = $entregados = $usados = $inservibles = 0;

        $insumos = Insumos::all();
        foreach ($insumos as $insumo) {
            $in_almacen   += $insumo->in_almacen;
            $out_almacen  += $insumo->out_almacen;
            $disponibles  += $insumo->disponibles;
            $entregados   += $insumo->entregados;
            $usados       += $insumo->usados;
            $inservibles  += $insumo->inservible;
        }

        $hoy = Carbon::today()->format('Y-m-d');

        $por_tipo = [
            'Dañado de Fábrica' => 0,
            'Dañado en Local'   => 0,
            'Dañado y Devuelto' => 0,
            'Perdido'           => 0,
            'Vencido'           => 0,
        ];

        $incidencias = Incidencias::whereDate('fecha_incidencia', $hoy)->get();

        foreach ($incidencias as $incidencia) {
            if (isset($por_tipo[$incidencia->tipo])) {
                $por_tipo[$incidencia->tipo] += $incidencia->cantidad;
            }
        }

        // Mapear a tus variables antiguas si las usas en la vista
        $usados2      = $por_tipo['Dañado en Local'] + $por_tipo['Dañado y Devuelto'];
        $inservibles2 = $por_tipo['Dañado de Fábrica'] + $por_tipo['Perdido'] + $por_tipo['Vencido'];

        $out_almacen2 = 0;
        $entregados2  = 0;

        return view('graficas.index', compact(
            'fecha_actual',
            'in_almacen',
            'out_almacen',
            'disponibles',
            'entregados',
            'usados',
            'inservibles',
            'usados2',
            'inservibles2',
            'out_almacen2',
            'entregados2',
            'por_tipo'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'desde'   => 'required|date',
            'hasta'   => 'required|date|after_or_equal:desde',
            'generar' => 'required|in:PDF,GRAFICA',
        ]);

        $incidencias = Incidencias::whereBetween('fecha_incidencia', [
            $request->desde,
            $request->hasta,
        ])->get();

        $insumos = Insumos::all();

        // Historial de esas incidencias (si quieres usarlo en el reporte)
        $historial = HistorialIncidencias::whereIn('id_incidencia', $incidencias->pluck('id'))->get();


        $historialPorIncidencia = $historial
            ->groupBy('id_incidencia')
            ->map(function ($items) {
                return $items->pluck('codigo')->implode(', ');
            })
            ->toArray();



        if ($request->generar === 'PDF') {

            if ($incidencias->isEmpty()) {
                return redirect()
                    ->to('reportes')
                    ->with('warning', 'No existen incidencias en el rango seleccionado para generar PDF.');
            }

            $pdf = Pdf::loadView('graficas.PDF.reportePDF', [
                    'incidencias'            => $incidencias,
                    'insumos'                => $insumos,
                    'historialPorIncidencia' => $historialPorIncidencia,
                    'desde'                  => $request->desde,
                    'hasta'                  => $request->hasta,
                ])
                ->setOptions([
                    'isRemoteEnabled'        => false,
                    'isPhpEnabled'           => false,
                    'isHtml5ParserEnabled'   => true,
                    'isFontSubsettingEnabled'=> false,
                ]);

            $pdf->setPaper('A4', 'landscape');

            return $pdf->stream('Reporte_Incidencias.pdf');
        }

        // Opción GRAFICA
        $in_almacen = $out_almacen = $disponibles = $entregados = $usados = $inservibles = 0;

        foreach ($insumos as $insumo) {
            $in_almacen   += $insumo->in_almacen;
            $out_almacen  += $insumo->out_almacen;
            $disponibles  += $insumo->disponibles;
            $entregados   += $insumo->entregados;
            $usados       += $insumo->usados;
            $inservibles  += $insumo->inservible;
        }

        $por_tipo = [
            'Dañado de Fábrica' => 0,
            'Dañado en Local'   => 0,
            'Dañado y Devuelto' => 0,
            'Perdido'           => 0,
            'Vencido'           => 0,
        ];

        foreach ($incidencias as $inc) {
            if (isset($por_tipo[$inc->tipo])) {
                $por_tipo[$inc->tipo] += $inc->cantidad;
            }
        }

        $usados2      = $por_tipo['Dañado en Local'] + $por_tipo['Dañado y Devuelto'];
        $inservibles2 = $por_tipo['Dañado de Fábrica'] + $por_tipo['Perdido'] + $por_tipo['Vencido'];

        $out_almacen2 = 0;
        $entregados2  = 0;

        return view('graficas.show', compact(
            'in_almacen',
            'out_almacen',
            'disponibles',
            'entregados',
            'usados',
            'inservibles',
            'out_almacen2',
            'entregados2',
            'usados2',
            'inservibles2',
            'por_tipo',
            'incidencias',
            'historial'
        ));
    }
}
