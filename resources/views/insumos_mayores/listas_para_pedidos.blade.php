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
            {{-- Botón para Carga Masiva --}}
           <!--  <form action="{{ route('insumos.importar') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label>Seleccionar Archivo (CSV/Excel)</label>
                <input type="file" name="archivo" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-success  ">Procesar Oferta</button>
        </form> -->
          
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
                      <th>Fecha Inicio</th>
                      <th>Fecha Fin</th>
                      <th>Monto Mínimo</th>
                      <th>Estado</th>
                      <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($listas as $lista)
                  <tr>
                      <td>{{ $lista->fecha_inicio }}</td>
                      <td>{{ $lista->fecha_fin }}</td>
                      <td>{{ number_format($lista->monto_minimo, 2) }}</td>
                      <td>
                          <span class="badge badge-primary">{{ $lista->estado }}</span>
                      </td>
                      <td>
                          {{-- Enlace a tu vista de productos específica --}}
                          <a href="{{ route('insumos-mayores.items', $lista->id) }}" class="btn btn-info btn-sm">
                              <i class="fa fa-eye"></i> Ver Productos
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