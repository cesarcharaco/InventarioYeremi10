@extends('layouts.app')

@section('title') Clientes Pendientes @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-user-plus"></i> Clientes por Activar</h1>
      <p>Listado de solicitudes de registro pendientes de aprobación.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item">Clientes</li>
      <li class="breadcrumb-item active">Pendientes</li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row">
      <div class="col-md-12">
        <div class="tile-body">
          @include('layouts.partials.flash-messages')
          
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="sampleTable">
              <thead>
                <tr>
                  <th>Identificación</th>
                  <th>Nombre y Apellido</th>
                  <th>Teléfono</th>
                  <th>Sede Solicitada</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                @foreach($clientes as $cliente)
                <tr>
                  <td><span class="badge badge-warning">{{ $cliente->identificacion }}</span></td>
                  <td>{{ $cliente->nombre }}</td>
                  <td>{{ $cliente->telefono }}</td>
                  <td>{{ $cliente->local->nombre ?? 'N/A' }}</td>
                  <td>
                    {{-- Botón de Activar --}}
                    <form action="{{ route('clientes.activar', $cliente->id) }}" method="POST" style="display:inline;">
                      @csrf @method('PATCH')
                      <button type="submit" class="btn btn-success btn-sm" 
                              data-toggle="tooltip" title="Aprobar Registro">
                        <i class="fa fa-check"></i> Activar
                      </button>
                    </form>
                    
                    {{-- Botón para ver más detalles antes de aprobar --}}
                    <a href="{{ route('clientes.show', $cliente->id) }}" class="btn btn-info btn-sm" 
                       data-toggle="tooltip" title="Ver Detalles">
                       <i class="fa fa-eye"></i>
                    </a>
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
  $(document).ready(function() {
    // Reutilizamos la misma lógica de inicialización de tu index
    if ( ! $.fn.DataTable.isDataTable( '#sampleTable' ) ) {
        $('#sampleTable').DataTable({
          "language": { 
              "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" 
          },
          "responsive": true,
          "autoWidth": false
        });
    }
    $('[data-toggle="tooltip"]').tooltip();
  });
</script>
@endsection