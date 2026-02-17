@extends('layouts.app')
@section('title') Categorías @endsection
@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-tags"></i> Categorías</h1>
      <p>Sistema Administrativo | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="{{ route('categorias.index') }}">Categorías</a></li>
      <li class="breadcrumb-item"><a href="">Listado</a></li>
    </ul>
  </div>
  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head" id="indicators">Categorías
            {{-- PERMISO: Solo quienes pueden crear configuraciones ven el botón --}}
            @can('crear-configuracion')
            <a class="btn btn-primary icon-btn pull-right" href="{{ route('categorias.create') }}">
              <i class="fa fa-plus"></i> Registrar Categoría
            </a>
            @endcan
          </h2>
        </div>
        <div class="basic-tb-hd text-center">            
          @include('layouts.partials.flash-messages')
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
                    <th>Categoría</th>
                    <th># Insumos</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($categorias as $key)
                  <tr>
                    <td>{{ $key->categoria }}</td>
                    <td>
                      <span class="badge badge-info">{{ $key->insumos_count }}</span>
                    </td>
                    
                    <td>
                      {{-- PERMISO: Editar --}}
                      @can('editar-configuracion')
                      <a href="{{ route('categorias.edit', $key->id) }}" 
                         class="btn btn-info btn-sm" 
                         data-toggle="tooltip" 
                         data-placement="top" 
                         title="Editar Categoría">
                        <i class="fa fa-edit"></i>
                      </a>
                      @endcan
                       {{-- PERMISO: Eliminar --}}
                      @can('eliminar-configuracion')
                      <a href="javascript:;" 
                         class="btn btn-danger btn-sm" 
                         data-toggle="modal" 
                         data-target="#eliminar_Categoria" 
                         onclick="eliminar('{{ $key->id }}')">
                        <i class="fa fa-trash"></i>
                      </a>
                      @endcan

                      @cannot('editar-configuracion')
                      <span class="badge badge-light">Lectura</span>
                      @endcannot
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

{{-- Modal Eliminar --}}
<div class="bs-component">
  <div class="modal" id="eliminar_Categoria">
    <div class="modal-dialog modal-dialog_1" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa fa-trash"></i> Eliminar Categoría</h5>
          <button class="close" type="button" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">×</span>
          </button>
        </div>
        {!! Form::open(['route' => ['categorias.destroy', 0], 'method' => 'DELETE', 'id' => 'form-eliminar-categoria']) !!}
            <div class="modal-body">
                <h2>¿Está seguro que desea eliminar esta categoría?</h2>
                <p>Esta acción no se podrá deshacer.</p>
                <input type="hidden" name="id_categoria" id="id_categoria1">
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" type="submit">Eliminar</button>
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cerrar</button>
            </div>          
        {!! Form::close() !!}
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">

  function eliminar(id_categoria) {
   $("#id_categoria1").val(id_categoria);
   $('#form-eliminar-categoria').attr('action', "{{ url('categorias') }}/" + id_categoria);
}

  function detalle(id_categoria) {
    // Aquí puedes agregar lógica para mostrar detalles
    alert('Funcionalidad de detalle en desarrollo');
  }

  $(document).ready(function() {
    // Verificamos si ya existe para evitar el error 
    if ( ! $.fn.DataTable.isDataTable( '#sampleTable' ) ) {
        $('#sampleTable').DataTable({
          "language": { 
              "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" 
          },
          "responsive": true,
          "autoWidth": false
        });
    }
  });
</script>
@endsection
