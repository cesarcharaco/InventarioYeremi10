@extends('layouts.app')

@section('title', 'Nuevo Proveedor')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark"><i class="fas fa-plus-circle mr-2"></i>Registrar Proveedor</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('proveedores.index') }}">Proveedores</a></li>
                <li class="breadcrumb-item active">Nuevo</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    {{-- Mensajes de error de validación --}}
    @include('layouts.partials.flash-messages')

    <div class="row justify-content-center">
        <div class="col-md-8 col-sm-12">
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header">
                    <h3 class="card-title">Datos del Proveedor</h3>
                </div>
                
                <form action="{{ route('proveedores.store') }}" method="POST" autocomplete="off">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            {{-- Nombre / Razón Social --}}
                            <div class="col-md-12 form-group">
                                <label for="nombre">Nombre o Razón Social <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" id="nombre" 
                                       class="form-control @error('nombre') is-invalid @enderror" 
                                       placeholder="Ej: Inversiones Repuestos C.A." 
                                       value="{{ old('nombre') }}" required>
                                @error('nombre')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- RIF --}}
                            <div class="col-md-6 form-group">
                                <label for="rif">RIF <span class="text-danger">*</span></label>
                                <input type="text" name="rif" id="rif" 
                                       class="form-control @error('rif') is-invalid @enderror" 
                                       placeholder="J-12345678-9" 
                                       value="{{ old('rif') }}" required>
                                <small class="text-muted">Formato: J-00000000-0 o V-00000000-0</small>
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
                                           placeholder="0412-1234567" 
                                           value="{{ old('telefono') }}">
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
                                           placeholder="contacto@proveedor.com" 
                                           value="{{ old('email') }}">
                                </div>
                            </div>

                            {{-- Dirección --}}
                            <div class="col-md-12 form-group">
                                <label for="direccion">Dirección Física</label>
                                <textarea name="direccion" id="direccion" rows="2" 
                                          class="form-control" 
                                          placeholder="Ubicación de la oficina o depósito">{{ old('direccion') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-white">
                        <div class="row">
                            <div class="col-6">
                                <a href="{{ route('proveedores.index') }}" class="btn btn-secondary btn-block">
                                    <i class="fas fa-times mr-1"></i> Cancelar
                                </a>
                            </div>
                            <div class="col-6">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-save mr-1"></i> Guardar
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
        // Convertir el RIF a mayúsculas automáticamente mientras escribe
        $('#rif').on('input', function() {
            this.value = this.value.toUpperCase();
        });
    });
</script>
@endsection