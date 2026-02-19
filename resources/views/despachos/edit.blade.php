@extends('layouts.app')

@section('title') Editar Despacho: {{ $despacho->codigo }} @endsection

@section('content')
<main class="app-content">
    {{-- 1. VERIFICACIÓN DE PERMISO DE ACCESO (ESTILO CATEGORÍAS) --}}
    @cannot('editar-despacho')
        <div class="tile text-center">
            <h1 class="text-danger"><i class="fa fa-lock"></i> Acceso Restringido</h1>
            <p>No tienes permisos para editar este despacho.</p>
            <a href="{{ route('despacho.index') }}" class="btn btn-primary">Volver al historial</a>
        </div>
    @else
        {{-- 2. VALIDACIÓN DE ALCANCE (NUEVA LÓGICA) --}}
        {{-- Si no es admin y el despacho no salió de su local, bloqueamos la edición --}}
        @if(!Auth::user()->can('seleccionar-cualquier-origen') && auth()->user()->localActual()->id != $despacho->id_local_origen)
            <div class="tile text-center">
                <h1 class="text-warning"><i class="fa fa-exclamation-triangle"></i> Acción no permitida</h1>
                <p>Solo puedes editar despachos que se originaron en tu local asignado ({{ auth()->user()->localActual()->nombre }}).</p>
                <a href="{{ route('despacho.index') }}" class="btn btn-primary">Volver al historial</a>
            </div>
        @else

        <div class="app-title">
            <div>
                <h1><i class="fa fa-edit"></i> Editar Despacho #{{ $despacho->codigo }}</h1>
                <p>Modificación de traslado y ajuste automático de stocks</p>
            </div>
            <ul class="app-breadcrumb breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home"></i></a></li>
                <li class="breadcrumb-item"><a href="{{ route('despacho.index') }}">Despachos</a></li>
                <li class="breadcrumb-item">Editar</li>
            </ul>
        </div>

        <form action="{{ route('despacho.update', $despacho->id) }}" method="POST" id="form-edit-despacho">
            @csrf
            @method('PUT')
            
            <div class="row">
                {{-- Sección de Cabecera --}}
                <div class="col-md-4">
                    <div class="tile">
                        <h4 class="tile-title border-bottom pb-2">Datos Generales</h4>
                        <div class="tile-body">
                            <div class="form-group">
                                <label class="font-weight-bold">Código de Despacho</label>
                                <input type="text" name="codigo" class="form-control-plaintext text-primary font-weight-bold" value="{{ $despacho->codigo }}" readonly>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Local Origen</label>
                                <input type="hidden" name="id_local_origen" value="{{ $despacho->id_local_origen }}">
                                <input type="text" class="form-control" value="{{ $despacho->origen->nombre }}" disabled>
                                <small class="text-muted text-uppercase">El origen no puede cambiarse en edición por seguridad de stock.</small>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Local Destino</label>
                                <input type="hidden" name="id_local_destino" value="{{ $despacho->id_local_destino }}">
                                <input type="text" class="form-control" value="{{ $despacho->destino->nombre }}" disabled>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Transportista <b class="text-danger">*</b></label>
                                <input type="text" name="transportado_por" class="form-control" value="{{ old('transportado_por', $despacho->transportado_por) }}" required>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Vehículo / Placa</label>
                                <input type="text" name="vehiculo_placa" class="form-control" value="{{ old('vehiculo_placa', $despacho->vehiculo_placa) }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sección de Insumos (Tabla Dinámica) --}}
                <div class="col-md-8">
                    <div class="tile">
                        <h4 class="tile-title border-bottom pb-2">Selección de Insumos</h4>
                        
                        <div class="row mb-3 align-items-end">
                            <div class="col-md-8">
                                <label class="font-weight-bold">Buscar Insumo para Agregar</label>
                                <select class="form-control select2" id="select_insumo">
                                    <option value="">--- Buscar producto ---</option>
                                    @foreach($insumos as $insumo)
                                        <option value="{{ $insumo->id }}" data-nombre="{{ $insumo->nombre }}">
                                            {{ $insumo->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-info btn-block" id="btn_agregar_fila">
                                    <i class="fa fa-plus-circle"></i> Agregar a la Lista
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="tabla_items">
                                <thead class="bg-primary text-white">
                                    <tr>
                                        <th>Insumo</th>
                                        <th width="150">Cantidad</th>
                                        <th width="50"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($despacho->detalles as $detalle)
                                    <tr id="fila_{{ $detalle->id_insumo }}">
                                        <td>
                                            <input type="hidden" name="id_insumo[]" value="{{ $detalle->id_insumo }}">
                                            <span class="font-weight-bold text-uppercase">{{ $detalle->insumo->nombre }}</span>
                                        </td>
                                        <td>
                                            <input type="number" name="cantidad[]" class="form-control form-control-sm" 
                                                   value="{{ $detalle->cantidad }}" min="1" required>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="eliminarFila('{{ $detalle->id_insumo }}')">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="tile-footer d-flex justify-content-end">
                            <a href="{{ route('despacho.index') }}" class="btn btn-secondary mr-2">
                                <i class="fa fa-times-circle"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-save"></i> Guardar Cambios y Actualizar Stock
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        @endif
    @endcannot
</main>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });

        // Evento para agregar nuevas filas
        $('#btn_agregar_fila').click(function() {
            let id = $('#select_insumo').val();
            let nombre = $('#select_insumo option:selected').data('nombre');

            if (!id) return Swal.fire('Error', 'Selecciona un insumo', 'warning');

            // Verificar si ya existe en la tabla
            if ($('#fila_' + id).length > 0) {
                return Swal.fire('Atención', 'Este insumo ya está en la lista', 'info');
            }

            let fila = `
                <tr id="fila_${id}">
                    <td>
                        <input type="hidden" name="id_insumo[]" value="${id}">
                        <span class="font-weight-bold text-uppercase">${nombre}</span>
                        <span class="badge badge-success ml-2">Nuevo</span>
                    </td>
                    <td>
                        <input type="number" name="cantidad[]" class="form-control form-control-sm" value="1" min="1" required>
                    </td>
                    <td>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="eliminarFila('${id}')">
                            <i class="fa fa-times"></i>
                        </button>
                    </td>
                </tr>
            `;

            $('#tabla_items tbody').append(fila);
            $('#select_insumo').val('').trigger('change');
        });
    });

    function eliminarFila(id) {
        $('#fila_' + id).remove();
    }
</script>
@endsection