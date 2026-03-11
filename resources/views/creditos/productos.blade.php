@extends('layouts.app')
@section('title') Productos del Cliente @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-list-ul"></i> Productos a Crédito</h1>
      <p>Historial detallado de insumos para: <strong>{{ $cliente->nombre }}</strong></p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="{{ route('creditos.show', $cliente->id) }}">Créditos</a></li>
      <li class="breadcrumb-item">Productos</li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row mb-3">
        <div class="col-md-12">
            <a class="btn btn-secondary" href="{{ route('creditos.show', $cliente->id) }}">
                <i class="fa fa-arrow-left"></i> Regresar al Perfil
            </a>
        </div>
    </div>

    <div class="tile-body">
      <div class="table-responsive">
        <table class="table table-hover table-bordered" id="tabla-productos-completa">
          <thead>
            <tr>
              <th>Fecha Venta</th>
              <th>Factura #</th>
              <th>Producto</th>
              <th>Descripción</th>
              <th>Categoría</th>
              <th>Serial</th>
              <th>Cant.</th>
            </tr>
          </thead>
          <tbody>
            @foreach($detalles as $d)
            <tr>
              <td>{{ $d->created_at->format('d/m/Y') }}</td>
              <td><span class="badge badge-secondary">{{ $d->venta->codigo_factura }}</span></td>
              <td><strong>{{ $d->insumo->producto }}</strong></td>
              <td class="text-muted">{{ $d->insumo->descripcion }}</td>
              <td><span class="badge badge-info">{{ $d->insumo->categoria->nombre ?? 'N/A' }}</span></td>
              <td>{{ $d->insumo->serial }}</td>
              <td class="text-center font-weight-bold">{{ $d->cantidad }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script type="text/javascript">
  $(document).ready(function() {
    if ( ! $.fn.DataTable.isDataTable( '#tabla-productos-completa' ) ) {
        $('#tabla-productos-completa').DataTable({
            "pageLength": 10,
            "responsive": true,
            "order": [[0, 'desc']],
            "language": { 
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" 
            }
        });
    }
  });
</script>
@endsection