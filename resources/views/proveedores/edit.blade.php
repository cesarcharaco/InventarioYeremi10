@extends('layouts.app')

@section('title', 'Editar Proveedor')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark"><i class="fas fa-edit mr-2"></i>Editar Proveedor</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('proveedores.index') }}">Proveedores</a></li>
                <li class="breadcrumb-item active">Editar</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    {{-- Mensajes de error de validación --}}
    @include('layouts.partials.flash-messages')

    <div class="row justify-content-center">
        <div class="col-md-8 col-sm-12">
            <div class="card card-outline card-warning shadow-sm">
                <div class="card-header">
                    <h3 class="card-title">Modificar datos de: <strong>{{ $proveedor->nombre }}</strong></h3>
                </div>
                
                {{-- Nota: Usamos POST porque en las rutas definimos Route::post('/{id}/actualizar'...) --}}
                <form action="{{ route('proveedores.update', $proveedor->id) }}" method="POST" autocomplete="off">
                    @csrf
                    {{-- Si usaras Route::put en web.php, aquí deberías poner @method('PUT') --}}
                    
                    <div class="card-body">
                        <div class="row">
                            {{-- Nombre / Razón Social --}}
                            <div class="col-md-12 form-group">
                                <label for="nombre">Nombre o Razón Social <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" id="nombre" 
                                       class="form-control @error('nombre') is-invalid @enderror" 
                                       value="{{ old('nombre', $proveedor->nombre) }}" required>
                                @error('nombre')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- RIF --}}
                            <div class="col-md-6 form-group">
                                <label for="rif">RIF <span class="text-danger">*</span></label>
                                <input type="text" name="rif" id="rif" 
                                       class="form-control @error('rif') is-invalid @enderror" 
                                       value="{{ old('rif', $proveedor->rif) }}" required>
                                <small class="text-muted">Formato: J-00000000-0</small>
                                @error('rif')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Teléfono --}}
                            <div class="col-md-6 form-group">
                                <label for="telefono">Teléfono de Contacto</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    </div>
                                    <input type="text" name="telefono" id="telefono" 
                                           class="form-control" 
                                           value="{{ old('telefono', $proveedor->telefono) }}">
                                </div>
                            </div>

                            {{-- Email --}}
                            <div class="col-md-12 form-group">
                                <label for="email">Correo Electrónico</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    </div>
                                    <input type="email" name="email" id="email" 
                                           class="form-control" 
                                           value="{{ old('email', $proveedor->email) }}">
                                </div>
                            </div>

                            {{-- Dirección --}}
                            <div class="col-md-12 form-group">
                                <label for="direccion">Dirección Física</label>
                                <textarea name="direccion" id="direccion" rows="2" 
                                          class="form-control">{{ old('direccion', $proveedor->direccion) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-white">
                        <div class="row">
                            <div class="col-6">
                                <a href="{{ route('proveedores.index') }}" class="btn btn-secondary btn-block">
                                    <i class="fas fa-arrow-left mr-1"></i> Volver
                                </a>
                            </div>
                            <div class="col-6">
                                <button type="submit" class="btn btn-warning btn-block">
                                    <i class="fas fa-sync-alt mr-1"></i> Actualizar
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Forzar mayúsculas en RIF
        $('#rif').on('input', function() {
            this.value = this.value.toUpperCase();
        });
    });
</script>
@endsection