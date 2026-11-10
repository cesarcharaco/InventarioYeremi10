@extends('layouts.app')

@section('title', 'Generar Plantilla de Pedido al Mayor')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-file-excel text-success mr-2"></i>Generar Plantilla Depósito</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('insumos-mayores.gestion') }}">Mayorista</a></li>
                    <li class="breadcrumb-item active">Generar Plantilla</li>
                </ol>
            </div>
        </div>
    </div>
@@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            
            {{-- Mensajes de Notificación --}}
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="icon fas fa-ban"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="card card-primary card-outline shadow-sm">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas fa-filter text-primary mr-1"></i> Filtros para Exportación de Stock
                    </h3>
                </div>
                
                {{-- Formulario que procesará la exportación --}}
                <form action="{{ route('insumos-mayores.exportar_plantilla') }}" method="POST" id="formGenerarPlantilla">
                    @csrf
                    <div class="card-body">
                        <div class="callout callout-info">
                            <h5><i class="fas fa-info-circle text-info"></i> Instrucciones</h5>
                            <p class="mb-0">
                                Seleccione los <strong>Depósitos</strong> de donde desea extraer la disponibilidad física del stock. 
                                Opcionalmente puede filtrar por una o más <strong>Categorías</strong>. El archivo Excel generado tendrá la estructura exacta requerida para ser cargado posteriormente en la sección de <em>Cargar Ofertas</em>.
                            </p>
                        </div>

                        <div class="row mt-4">
                            {{-- Selector Múltiple de Depósitos --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="depositos" class="font-weight-bold">
                                        Depósitos de Origen <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-warehouse"></i></span>
                                        </div>
                                        <select name="depositos[]" id="depositos" class="form-control select2" multiple="multiple" data-placeholder="Seleccione uno o varios depósitos" style="width: 90%;" required>
                                            @foreach($depositos as $deposito)
                                                <option value="{{ $deposito->id }}" {{ (is_array(old('depositos')) && in_array($deposito->id, old('depositos'))) ? 'selected' : '' }}>
                                                    {{ $deposito->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <small class="form-text text-muted">Solo se procesarán artículos con existencia mayor a 0 en las sedes seleccionadas.</small>
                                </div>
                            </div>

                            {{-- Selector Múltiple de Categorías --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="categorias" class="font-weight-bold">
                                        Categorías de Repuestos <small class="text-muted">(Opcional - Dejar vacío para todas)</small>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <select name="categorias[]" id="categorias" class="form-control select2" multiple="multiple" data-placeholder="Todas las categorías" style="width: 90%;">
                                            @foreach($categorias as $categoria)
                                                <option value="{{ $categoria->id }}" {{ (is_array(old('categorias')) && in_array($categoria->id, old('categorias'))) ? 'selected' : '' }}>
                                                    {{ $categoria->categoria }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <small class="form-text text-muted">Filtre familias específicas de productos si desea un catálogo acotado.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer text-right bg-light">
                        <a href="{{ route('insumos-mayores.gestion') }}" class="btn btn-secondary mr-2">
                            <i class="fas fa-times-circle mr-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-success font-weight-bold" id="btnDescargar">
                            <i class="fas fa-file-excel mr-1"></i> Generar y Descargar Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Inicializar Select2 con soporte para selección múltiple
        $('.select2').select2({
            theme: 'bootstrap4',
            allowClear: true,
            closeOnSelect: false
        });
    });
</script>
@endpush