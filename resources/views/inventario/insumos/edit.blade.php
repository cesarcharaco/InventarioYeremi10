@extends('layouts.app')
@section('title') Editar Insumo @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-th-list"></i> SAYER</h1>
      <p>Sistema Administrativo | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item">SAYER</li>
      <li class="breadcrumb-item"><a href="{{ route('insumos.index') }}">Insumos</a></li>
      <li class="breadcrumb-item">Modificar</li>
    </ul>
  </div>

  <div class="tile">
    <div class="row">
        <div class="col-md-12">
            @if(Gate::denies('editar-datos-maestros'))
                <div class="alert alert-info border-left-info">
                    <i class="fa fa-info-circle"></i> <strong>Modo Ajuste:</strong> Solo puedes modificar parámetros de stock. Los datos maestros están protegidos.
                </div>
            @endif
        </div>
    </div>
    <h4 class="line-head">Editar: {{ $insumo->producto }}</h4>
    
    {!! Form::open(['route' => ['insumos.update', $insumo->id], 'method' => 'PUT', 'data-parsley-validate']) !!}
      @csrf
      
      <div class="row">
        {{-- Bloque de Identificación --}}
        <div class="col-md-3">
          <div class="form-group">
            <label>Nombre del Producto <b class="text-danger">*</b></label>
            <input class="form-control" type="text" name="producto" value="{{ $insumo->producto }}" required>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>Categoría <b class="text-danger">*</b></label>
            {!! Form::select('categoria_id', $categorias, $insumo->categoria_id, ['class' => 'form-control', 'required']) !!}
          </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>
                    Descripción detallada
                    <span id="badge-coincidencia" class="ml-2"></span>
                </label>
                <input type="text" class="form-control" name="descripcion" id="input_descripcion" 
                       value="{{ $insumo->descripcion }}" autocomplete="off"
                       @cannot('editar-datos-maestros') readonly @endcannot>
                <div id="lista-coincidencias" class="invalid-feedback" style="display: none; font-size: 0.9em;">
                    <!-- Aquí se inyectarán las coincidencias -->
                </div>
            </div>
        </div>
      </div>

      <div class="row">
        {{-- Bloque de Ventas --}}
        <div class="col-md-4">
          <div class="form-group">
            <label>Modelo de Venta <b class="text-danger">*</b></label>
            {!! Form::select('modelo_venta_id', $modelos, $insumo->modelo_venta_id, ['class' => 'form-control', 'required',
                Gate::denies('editar-datos-maestros') ? 'disabled' : '']) !!}
                @if(Gate::denies('editar-datos-maestros'))
                <input type="hidden" name="modelo_venta_id" value="{{ $insumo->modelo_venta_id }}">
                @endif
            <small class="text-muted">El cambio de modelo recalculará los precios de venta.</small>
          </div>
        </div>

        {{-- Parámetros de Alerta de Inventario --}}
        <div class="col-md-4">
          <div class="form-group">
            <label>Stock Mínimo (Alerta de reposición)</label>
            <input class="form-control" type="number" name="stock_min" value="{{ $insumo->stock_min }}" min="0">
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>Stock Máximo (Capacidad ideal)</label>
            <input class="form-control" type="number" name="stock_max" value="{{ $insumo->stock_max }}" min="0">
          </div>
        </div>
        <div class="col-md-4 form-group">
            <label class="font-weight-bold">Serial / Código de Barra</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fa fa-barcode"></i></span>
                </div>
                <input type="text" 
                       name="serial" 
                       id="serial" 
                       class="form-control" 
                       value="{{ $insumo->serial }}" 
                       placeholder="Escanear o editar serial">
            </div>
        </div>
      </div>
      <hr>
      <h5 class="text-primary mb-3"><i class="fa fa-toggle-on mr-1"></i> Gestión de Estados de Disponibilidad</h5>

      <div class="row">
        {{-- 1. ESTADO GLOBAL DEL INSUMO --}}
        <div class="col-md-4 form-group">
          <label class="font-weight-bold">Estado Global (Catálogo General)</label>
          <select name="estado" id="select_estado_global" class="form-control" @cannot('gestionar-estado-global') disabled @endcannot>
            <option value="En Venta" {{ $insumo->estado == 'En Venta' ? 'selected' : '' }}>En Venta</option>
            <option value="Suspendido" {{ $insumo->estado == 'Suspendido' ? 'selected' : '' }}>Suspendido</option>
            <option value="No Disponible" {{ $insumo->estado == 'No Disponible' ? 'selected' : '' }}>No Disponible</option>
          </select>
          @cannot('gestionar-estado-global')
            <small class="text-muted"><i class="fa fa-lock"></i> No tienes permiso para cambiar el estado global.</small>
          @else
            <small class="text-muted" id="ayuda_estado_global">Afecta la visibilidad del producto en todo el sistema.</small>
          @endcannot
        </div>

        {{-- 2. ESTADO LOCAL POR SUCURSAL / DEPÓSITO --}}
        <div class="col-md-8 form-group">
          <label class="font-weight-bold">Estado por Sucursal / Depósito</label>
          @if($stocksLocales->isEmpty())
            <div class="alert alert-light border py-2 mb-0">
              <small class="text-muted"><i class="fa fa-info-circle mr-1"></i> Este insumo aún no tiene existencias registradas en ningún local.</small>
            </div>
          @else
            <div class="table-responsive">
              <table class="table table-bordered table-sm mb-0 bg-white">
                <thead class="thead-light">
                  <tr>
                    <th>Sucursal / Depósito</th>
                    <th width="20%" class="text-center">Stock Actual</th>
                    <th width="35%">Estado en Local</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($stocksLocales as $sl)
                    <tr>
                      <td class="align-middle">
                        <i class="fa fa-store text-primary mr-1"></i> <strong>{{ $sl->nombre_local }}</strong>
                      </td>
                      <td class="text-center align-middle">
                        <span class="badge badge-secondary px-2 py-1">{{ $sl->cantidad }}</span>
                      </td>
                      <td class="align-middle">
                        @php
                            $puedeEditarLocal = Gate::allows('gestionar-estado-local', $sl->id_local);
                        @endphp
                        <select name="estados_locales[{{ $sl->id_local }}]" 
                                class="form-control form-control-sm select-estado-local"
                                data-permiso="{{ $puedeEditarLocal ? '1' : '0' }}"
                                {{ !$puedeEditarLocal ? 'disabled' : '' }}>
                          <option value="Disponible" {{ $sl->estado_local == 'Disponible' ? 'selected' : '' }}>Disponible</option>
                          <option value="Suspendido" {{ $sl->estado_local == 'Suspendido' ? 'selected' : '' }}>Suspendido</option>
                        </select>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
      <div class="tile-footer">
        <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Guardar Cambios</button>
        <a class="btn btn-secondary" href="{{ route('insumos.index') }}"><i class="fa fa-arrow-left"></i> Volver</a>
      </div>
    {!! Form::close() !!}
  </div>
</main>
@endsection
@section('scripts')
<script>
    $(document).ready(function() {
        let temporizador;
        const $inputDesc =$('#input_descripcion');
        const $badge =$('#badge-coincidencia');
        const $lista =$('#lista-coincidencias');

        $inputDesc.on('input', function() {
            clearTimeout(temporizador);
            let texto = $(this).val().trim();

            // Si hay menos de 3 caracteres, limpiamos las alertas
            if(texto.length < 3) {
                limpiarClases();
                return;
            }

            temporizador = setTimeout(function() {
                $.ajax({
                    url: '{{ route("insumos.verificar_descripcion") }}',
                    type: 'GET',
                    data: { query: texto },
                    success: function(response) {
                        limpiarClases();
                        
                        if(response.coincidencias.length > 0) {
                            // Encontró coincidencias: Poner Rojo
                            $inputDesc.addClass('is-invalid');$badge.html('<span class="badge badge-danger"><i class="fa fa-exclamation-triangle"></i> Producto posiblemente duplicado</span>');
                            
                            let htmlMatches = '<strong>Coincidencias encontradas:</strong><ul class="mb-0 pl-3">';
                            response.coincidencias.forEach(item => {
                                htmlMatches += `<li>${item.producto} - ${item.descripcion}</li>`;
                            });
                            htmlMatches += '</ul>';
                            
                            $lista.html(htmlMatches).show();
                        } else {
                            // Sin coincidencias: Poner Verde
                            $inputDesc.addClass('is-valid');$badge.html('<span class="badge badge-success"><i class="fa fa-check"></i> Descripción única</span>');
                        }
                    }
                });
            }, 500); // 500ms de retraso (Debounce)
        });

        // --- VALIDACIÓN DE CONSISTENCIA ENTRE ESTADO GLOBAL Y ESTADOS LOCALES ---
        const $selectGlobal = $('#select_estado_global');
        const $selectsLocales = $('.select-estado-local');
        const $ayudaGlobal = $('#ayuda_estado_global');

        function sincronizarEstados() {
            let estadoGlobal = $selectGlobal.val();

            if (estadoGlobal === 'Suspendido') {
                // Regla: Si se suspende de forma global, no puede estar en venta en ningún local
                $selectsLocales.each(function() {
                    $(this).val('Suspendido').prop('disabled', true);
                });
                $ayudaGlobal.html('<span class="text-danger font-weight-bold"><i class="fa fa-ban"></i> Al estar suspendido globalmente, se suspende en todos los locales.</span>');
            } else {
                // Si está "En Venta" o "No Disponible", restauramos la edición según el permiso de cada local
                $selectsLocales.each(function() {
                    let tienePermiso = $(this).data('permiso') == '1';
                    $(this).prop('disabled', !tienePermiso);
                });
                $ayudaGlobal.text('Afecta la visibilidad del producto en todo el sistema.');
            }
        }

        // Ejecutar al cargar la página (por si ya venía suspendido) y cada vez que cambie
        sincronizarEstados();
        $selectGlobal.on('change', sincronizarEstados);

        // Antes de enviar el formulario, habilitamos temporalmente los selects locales
        // que fueron bloqueados por JS para que sus valores ('Suspendido') sí viajen en el POST
        $('form').on('submit', function() {
            if ($selectGlobal.val() === 'Suspendido') {
                $selectsLocales.each(function() {
                    if ($(this).data('permiso') == '1') {
                        $(this).prop('disabled', false);
                    }
                });
            }
        });
        function limpiarClases() {
            $inputDesc.removeClass('is-valid is-invalid');
            $badge.empty();$lista.hide().empty();
        }

    });
</script>
@endsection