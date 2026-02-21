@extends('layouts.app')

@section('title') Ventas @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-shopping-cart"></i> SAYER</h1>
      <p>Sistema Administrativo | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item">Ventas</li>
      <li class="breadcrumb-item">Historial</li>
    </ul>
  </div>

  {{-- Widget Informativo (Opcional: Si quieres recordar que los precios son por modelo) --}}
  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="page-header">
          <h2 class="mb-3 line-head" id="indicators">
            Historial de Ventas
            @can('operar-caja')
            <a class="btn btn-primary icon-btn pull-right" href="{{ route('ventas.create') }}">
              <i class="fa fa-plus"></i> Registrar Venta
            </a>
            @endcan
          </h2>
        </div>
        
        <div class="tile-body">
          <form action="{{ route('ventas.index') }}" method="GET" class="row mb-4">
              <div class="col-md-4">
                  <label>Desde:</label>
                  <input type="date" name="fecha_desde" class="form-control" value="{{ request('fecha_desde') }}">
              </div>
              <div class="col-md-4">
                  <label>Hasta:</label>
                  <input type="date" name="fecha_hasta" class="form-control" value="{{ request('fecha_hasta') }}">
              </div>
              <div class="col-md-4 d-flex align-items-end">
                  <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-filter"></i> Filtrar</button>
                  <a href="{{ route('ventas.index') }}" class="btn btn-secondary"><i class="fa fa-eraser"></i> Limpiar</a>
              </div>
          </form>
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="tabla-ventas">
              <thead>
                <tr class="bg-primary text-white">
                  <th>Fecha/Hora</th>
                  <th>Factura</th>
                  <th>Cliente</th>
                  @can('auditar-cajas')
                    <th>Sede</th>
                  @endcan
                  <th class="text-right">Total USD</th>
                  <th class="text-center">Estado</th>
                  <th class="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody>
                @foreach($ventas as $v)
                <tr>
                  <td>{{ $v->created_at->format('d/m/Y h:i A') }}</td>
                  <td><span class="badge badge-secondary">{{ $v->codigo_factura }}</span></td>
                  <td><strong>{{ $v->cliente->nombre }}</strong></td>
                  @can('auditar-cajas')
                    <td><i class="fa fa-store text-danger"></i> {{ $v->local->nombre }}</td>
                  @endcan
                  <td class="text-right text-primary font-weight-bold">
                    ${{ number_format($v->total_usd, 2) }}
                  </td>
                  <td class="text-center">
                    @if($v->estado == 'completada')
                      <span class="badge badge-success">COMPLETADA</span>
                    @else
                      <span class="badge badge-danger">ANULADA</span>
                    @endif
                  </td>
                  <td class="text-center">
                    <div class="btn-group">
                      <a href="{{ route('ventas.show', $v->id) }}" class="btn btn-info btn-sm" title="Ver Detalle">
                        <i class="fa fa-eye"></i>
                      </a>
                      @can('anular-historial')
                      <button class="btn btn-danger btn-sm" onclick="confirmarAnulacion({{ $v->id }})" title="Anular">
                        <i class="fa fa-ban"></i>
                      </button>
                      @endcan
                    </div>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script type="text/javascript">
  $(document).ready(function () {
    // Usamos exactamente tu objeto de lenguaje de Insumos
    var lenguajeEspanol = {
        "decimal": "",
        "emptyTable": "No hay información",
        "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
        "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
        "infoFiltered": "(Filtrado de _MAX_ entradas totales)",
        "thousands": ",",
        "lengthMenu": "Mostrar _MENU_ entradas",
        "loadingRecords": "Cargando...",
        "processing": "Procesando...",
        "search": "Buscar:",
        "zeroRecords": "Sin resultados encontrados",
        "paginate": { "first": "Primero", "last": "Último", "next": "Siguiente", "previous": "Anterior" }
    };

    try {
        $('#tabla-ventas').DataTable({
            "responsive": true,
            "language": lenguajeEspanol,
            "destroy": true,
            "order": [[ 0, "desc" ]] // Ordenar por fecha reciente
        });
    } catch (e) { console.log("Error en DataTable Ventas: ", e); }
  });

  function confirmarAnulacion(id) {
    Swal.fire({
        title: '¿Anular esta venta?',
        text: "El stock se reintegrará automáticamente.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, anular',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "{{ url('ventas/anular') }}/" + id;
        }
    });
  }
</script>
@endsection