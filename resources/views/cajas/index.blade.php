@extends('layouts.app')

@section('title') Auditoría de Cajas @endsection

@section('content')
@include('layouts.partials.flash-messages')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-vault"></i> Auditoría de Cajas</h1>
      <p>Control de cierres de jornada y conciliación de efectivo</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item">Cajas</li>
      <li class="breadcrumb-item">Historial</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="page-header">
          <h2 class="mb-3 line-head">
            Historial de Cierres
            @can('operar-caja')
            <a class="btn btn-primary icon-btn pull-right" href="{{ route('cajas.create') }}">
              <i class="fa fa-plus"></i> Abrir Nueva Jornada
            </a>
            @endcan
          </h2>
        </div>
        
        <div class="tile-body">
          <form action="{{ route('cajas.index') }}" method="GET" class="row mb-4">
              <div class="col-md-4">
                  <label>Ver Jornadas Desde:</label>
                  <input type="date" name="fecha_desde" class="form-control" value="{{ request('fecha_desde') }}">
              </div>
              <div class="col-md-4">
                  <label>Hasta:</label>
                  <input type="date" name="fecha_hasta" class="form-control" value="{{ request('fecha_hasta') }}">
              </div>
              <div class="col-md-4 d-flex align-items-end">
                  <button type="submit" class="btn btn-dark mr-2"><i class="fa fa-search"></i> Consultar</button>
                  <a href="{{ route('cajas.index') }}" class="btn btn-secondary"><i class="fa fa-refresh"></i> Ver Todo</a>
              </div>
          </form>

          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="tabla-cajas">
              <thead>
                <tr class="bg-dark text-white">
                  <th>ID</th>
                  <th>Sede</th>
                  <th>Cajero/Usuario</th>
                  <th>Apertura</th>
                  <th>Cierre</th>
                  <th class="text-right">Esperado (USD)</th>
                  <th class="text-right">Reportado (USD)</th>
                  <th class="text-center">Diferencia</th>
                  <th class="text-center">Estado</th>
                  <th class="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody>
                @foreach($cajas as $c)
                @php
                    // Sincronización con nombres de columna de la base de datos
                    $esperado = $c->monto_cierre_usd_efectivo ?? 0;
                    $reportado = $c->reportado_cierre_usd_efectivo ?? 0;
                    $diferencia = $reportado - $esperado;
                @endphp
                <tr @if($c->estado == 'anulada') class="table-danger" style="opacity: 0.7;" @endif>
                  <td>{{ $c->id }}</td>
                  <td><i class="fa fa-store text-danger"></i> {{ $c->local->nombre }}</td>
                  <td><strong>{{ $c->user->name }}</strong></td>
                  <td>{{ \Carbon\Carbon::parse($c->fecha_apertura)->format('d/m/y h:i A') }}</td>
                  <td>
                    {{ $c->fecha_cierre ? \Carbon\Carbon::parse($c->fecha_cierre)->format('d/m/y h:i A') : '---' }}
                  </td>
                  <td class="text-right font-weight-bold">${{ number_format($esperado, 2) }}</td>
                  <td class="text-right text-primary font-weight-bold">${{ number_format($reportado, 2) }}</td>
                  <td class="text-center">
                    @if($c->estado == 'cerrada')
                        @if($diferencia > 0)
                            <span class="text-success font-weight-bold">+${{ number_format($diferencia, 2) }}</span>
                        @elseif($diferencia < 0)
                            <span class="text-danger font-weight-bold">-${{ number_format(abs($diferencia), 2) }}</span>
                        @else
                            <span class="text-muted">Cuadrada</span>
                        @endif
                    @elseif($c->estado == 'anulada')
                        <span class="text-muted"><strike>N/A</strike></span>
                    @else
                        <span class="text-info">En curso...</span>
                    @endif
                  </td>
                  <td class="text-center">
                    @if($c->estado == 'abierta')
                        <span class="badge badge-info">ABIERTA</span>
                    @elseif($c->estado == 'cerrada')
                        <span class="badge badge-secondary">CERRADA</span>
                    @else
                        <span class="badge badge-danger">ANULADA</span>
                    @endif
                  </td>
                  <td class="text-center">
                    <div class="btn-group">
                      <a href="{{ route('cajas.edit', $c->id) }}" class="btn btn-sm {{ $c->estado == 'abierta' ? 'btn-warning' : 'btn-info' }}" title="Ver Detalles">
                        <i class="fa {{ $c->estado == 'abierta' ? 'fa-lock-open' : 'fa-eye' }}"></i>
                      </a>
                      
                      @if($c->estado != 'anulada' && auth()->user()->can('auditar-cajas'))
                          <button class="btn btn-danger btn-sm" onclick="confirmarAnulacion({{ $c->id }})" title="Anular Jornada">
                              <i class="fa fa-ban"></i>
                          </button>
                      @endif
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
    var lenguajeEspanol = {
        "decimal": "",
        "emptyTable": "No hay información",
        "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
        "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
        "infoFiltered": "(Filtrado de _MAX_ entradas totales)",
        "thousands": ",",
        "lengthMenu": "Mostrar _MENU_ entradas",
        "loadingRecords": "Cargando...",
        "search": "Buscar:",
        "zeroRecords": "Sin resultados encontrados",
        "paginate": { "first": "Primero", "last": "Último", "next": "Siguiente", "previous": "Anterior" }
    };

    $('#tabla-cajas').DataTable({
        "responsive": true,
        "language": lenguajeEspanol,
        "order": [[ 0, "desc" ]] 
    });
  });

  function confirmarAnulacion(id) {
      Swal.fire({
          title: '¿ANULAR ESTA JORNADA?',
          text: "Esta acción es irreversible. Se liberará al cajero para abrir una nueva caja.",
          icon: 'error',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#6c757d',
          confirmButtonText: '<i class="fa fa-trash"></i> Sí, Anular',
          cancelButtonText: 'Cancelar',
          reverseButtons: true,
          showLoaderOnConfirm: true,
          preConfirm: () => {
              return fetch(`{{ url('cajas/anular') }}/${id}`, {
                  method: 'POST',
                  headers: {
                      'X-CSRF-TOKEN': '{{ csrf_token() }}',
                      'Content-Type': 'application/json',
                      'Accept': 'application/json'
                  }
              })
              .then(response => {
                  if (!response.ok) { throw new Error('Error en el servidor'); }
                  return response.json();
              })
              .catch(error => {
                  Swal.showValidationMessage(`Solicitud fallida: ${error}`);
              });
          },
          allowOutsideClick: () => !Swal.isLoading()
      }).then((result) => {
          if (result.isConfirmed && result.value.success) {
              Swal.fire({
                  title: '¡Anulada!',
                  text: result.value.message,
                  icon: 'success'
              }).then(() => {
                  location.reload(); 
              });
          }
      });
  }
</script>
@endsection