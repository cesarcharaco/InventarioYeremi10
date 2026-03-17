@extends('layouts.app')

@section('title') Notificaciones @endsection

@section('content')
@include('layouts.partials.flash-messages')

<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-bell"></i> Notificaciones</h1>
      <p>Historial de alertas del sistema | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="#">Notificaciones</a></li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head" id="indicators">Historial
            @if(auth()->user()->unreadNotifications->count() > 0)
              <form action="{{ route('notifications.markAllRead') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm pull-right">
                  <i class="fa fa-check-double"></i> Marcar todas como leídas
                </button>
              </form>
            @endif
          </h2>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="tile">
          <div class="tile-body">
            <div class="table-responsive">
              <table class="table table-hover table-bordered" id="sampleTable">
                <thead>
                  <tr>
                    <th width="50">Estado</th>
                    <th>Notificación</th>
                    <th>Mensaje</th>
                    <th>Fecha</th>
                    <th width="100">Acción</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($notifications as $n)
                  <tr style="{{ $n->read_at ? 'opacity: 0.7;' : 'background-color: rgba(0, 123, 255, 0.05); border-left: 3px solid #007bff;' }}">
                    <td class="text-center">
                      @if($n->read_at)
                        <i class="fa fa-envelope-open text-muted" title="Leída"></i>
                      @else
                        <i class="fa fa-envelope text-primary" title="Nueva"></i>
                      @endif
                    </td>
                    <td>
                      <i class="{{ $n->data['icono'] ?? 'fa fa-info-circle' }} mr-2"></i>
                      <strong>{{ $n->data['titulo'] }}</strong>
                    </td>
                    <td>{{ $n->data['mensaje'] }}</td>
                    <td>
                      <span class="text-muted small">
                        <i class="fa fa-clock-o"></i> {{ $n->created_at->diffForHumans() }}
                      </span>
                    </td>
                    <td>
                      <a href="{{ route('notifications.read', $n->id) }}" 
                         class="btn btn-primary btn-sm btn-block">
                        <i class="fa fa-eye"></i> Ver
                      </a>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            {{-- Paginación en caso de que haya muchas --}}
            <div class="mt-3">
                {{ $notifications->links() }}
            </div>
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
    if ( ! $.fn.DataTable.isDataTable( '#sampleTable' ) ) {
        $('#sampleTable').DataTable({
          "language": { 
              "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" 
          },
          "responsive": true,
          "autoWidth": false,
          "order": [[3, "desc"]] // Ordenar por fecha (columna 3) descendente
        });
    }
  });
</script>
@endsection