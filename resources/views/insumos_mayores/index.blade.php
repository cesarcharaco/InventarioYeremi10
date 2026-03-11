@extends('layouts.app')
@section('title') Productos al Mayor @endsection

@section('content')
@include('layouts.partials.flash-messages')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-tags"></i> Productos al Mayor</h1>
      <p>Sistema Administrativo | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="#">Mayorista</a></li>
      <li class="breadcrumb-item"><a href="#">Listado</a></li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head" id="indicators">Productos al Mayor</h2>
                    
        </div>
      </div>
    </div>
            <br>

    <div class="row">
      <div class="col-md-12">
        <div class="tile">
          <div class="tile-body">
            <div class="table-responsive">
              <table class="table table-hover table-bordered" id="sampleTable">
                <thead>
                  <tr>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th>Aplicativo</th>
                    <th>Costo USD</th>
                    <th>Venta USD</th>
                    <th>Incremento</th>
                    <th>Estado</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($insumos as $item)
                  <tr>
                    <td>{{ $item->codigo }}</td>
                    <td>{{ $item->descripcion }}</td>
                    <td>{{ $item->aplicativo }}</td>
                    <td><strong>{{ number_format($item->costo_usd, 2) }} $</strong></td>
                    <td><span class="badge badge-success">{{ number_format($item->venta_usd, 2) }} $</span></td>
                    <td>{{ $item->listaOferta?->incremento ?? '0.00' }} %</td>
                    <td>
                        @if($item->estado == 'activo')
                            <span class="badge badge-primary">Activo</span>
                        @else
                            <span class="badge badge-secondary">Inactivo</span>
                        @endif
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
            "order": [[0, 'asc']]
        });
    }
  });
</script>
@endsection