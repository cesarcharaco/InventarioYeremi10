@extends('layouts.app')

@section('title', 'Listado de Proveedores')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark"><i class="fas fa-address-card mr-2"></i>Proveedores</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ url('home') }}">Inicio</a></li>
                <li class="breadcrumb-item active">Proveedores</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    {{-- Incluimos los mensajes de éxito/error --}}
    @include('layouts.partials.flash-messages')

    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title">Directorio de Proveedores</h3>
            <div class="card-tools">
                <a href="{{ route('proveedores.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus mr-1"></i> Nuevo Proveedor
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <div class="table-responsive">
                {{-- La clase 'sampleTable' activa el JS que ya tienes configurado --}}
                <table class="table table-bordered table-striped sampleTable dt-responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>Nombre / Razón Social</th>
                            <th>RIF</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($proveedores as $proveedor)
                            <tr>
                                <td>
                                    <strong>{{ $proveedor->nombre }}</strong>
                                </td>
                                <td><span class="badge badge-secondary">{{ $proveedor->rif }}</span></td>
                                <td>
                                    @if($proveedor->telefono)
                                        <a href="tel:{{ $proveedor->telefono }}" class="text-info">
                                            <i class="fas fa-phone-alt mr-1"></i> {{ $proveedor->telefono }}
                                        </a>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($proveedor->email)
                                        <small>{{ $proveedor->email }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="{{ route('proveedores.edit', $proveedor->id) }}" 
                                           class="btn btn-warning btn-xs" 
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <button type="button" 
                                                class="btn btn-danger btn-xs btn-eliminar" 
                                                data-id="{{ $proveedor->id }}"
                                                data-nombre="{{ $proveedor->nombre }}"
                                                title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>

                                        <form id="delete-form-{{ $proveedor->id }}" 
                                              action="{{ route('proveedores.destroy', $proveedor->id) }}" 
                                              method="POST" style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Confirmación de eliminación con SweetAlert2 (que ya tienes en scripts.blade)
        $('.btn-eliminar').click(function() {
            let id = $(this).data('id');
            let nombre = $(this).data('nombre');
            
            Swal.fire({
                title: '¿Eliminar proveedor?',
                text: "Se borrará a " + nombre + ". Esta acción no se puede deshacer si tiene registros asociados.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        });
    });
</script>
@endsection