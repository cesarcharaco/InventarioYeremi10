@extends('layouts.app')

@section('title') Precios de Insumos @endsection

@section('content')
<main class="app-content">
    {{-- 1. VERIFICACIÓN DE SEGURIDAD EN CAPA DE VISTA --}}
  @cannot('ver-costos')
    <div class="row">
        <div class="col-md-12">
            <div class="tile text-center">
                <h1 class="text-danger"><i class="fa fa-exclamation-triangle"></i> Acceso Restringido</h1>
                <p>No tienes permisos suficientes para gestionar los costos de la empresa.</p>
                <a href="{{ route('home') }}" class="btn btn-primary">Volver al Inicio</a>
            </div>
        </div>
    </div>
  @else
  {{-- SI TIENE PERMISO, MOSTRAR TODO EL CONTENIDO --}}
  <div class="app-title">
    <div>
      <h1><i class="fa fa-th-list"></i> SAYER</h1>
      <p>Sistema Administrativo | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item">SAYER</li>
      <li class="breadcrumb-item"><a href="{{ route('insumos.index') }}">Insumos</a></li>
      <li class="breadcrumb-item">Lista de Precios</li>
    </ul>
  </div>

  <div class="tile mb-4">
    <div class="tile-body">
      <div class="table-responsive">
        <table class="table table-hover table-bordered" id="tabla-precios">
          <thead>
            <tr class="bg-dark text-white">
              <th>Producto / Serial</th>
              <th class="text-center" width="180">Costo ($)</th>
              <th class="text-center" style="background-color: #27ae60; color: white;">Venta BCV ($)</th>
              <th class="text-center" style="background-color: #2980b9; color: white;">Venta BS</th>
              <th class="text-center" style="background-color: #e67e22; color: white;">Venta USDT</th>
              <th>Modelo de Venta</th>
            </tr>
          </thead>
          <tbody>
            @foreach($insumos as $key)
            <tr data-id="{{ $key->id }}" 
                data-tasa-bcv="{{ $key->tasa_bcv }}" 
                data-tasa-binance="{{ $key->tasa_binance }}"
                data-factor-bcv="{{ $key->factor_bcv }}" 
                data-factor-usdt="{{ $key->factor_usdt }}">
              
              <td>
                <strong>{{ $key->producto }}</strong><br>
                <small class="text-muted">{{ $key->serial }}</small>
              </td>

              {{-- CELDA EDITABLE DE COSTO --}}
              <td class="text-center celda-editable" style="cursor: pointer; position: relative;">
                <span class="costo-texto font-weight-bold text-secondary">${{ number_format($key->costo, 2) }}</span>
                @can('gestionar-insumos')
                <div class="contenedor-edicion d-none">
                  <input type="number" step="0.01" class="form-control input-costo form-control-sm" value="{{ $key->costo }}">
                  <div class="mt-1">
                    <button class="btn btn-success btn-sm btn-guardar"><i class="fa fa-check"></i></button>
                    <button class="btn btn-danger btn-sm btn-cancelar"><i class="fa fa-times"></i></button>
                  </div>
                </div>
                @endcan
              </td>

              {{-- RESULTADOS CALCULADOS --}}
              <td class="text-center font-weight-bold col-venta-usd" style="color: #1e8449; background-color: #eafaf1;">
                ${{ number_format($key->precio_venta_usd, 2) }}
              </td>

              <td class="text-center font-weight-bold col-venta-bs" style="color: #21618c; background-color: #ebf5fb;">
                {{ number_format($key->precio_venta_bs, 2) }} <small>Bs</small>
              </td>

              <td class="text-center font-weight-bold col-venta-usdt" style="color: #a04000; background-color: #fef5e7;">
                {{ number_format($key->precio_venta_usdt, 2) }} <small>USDT</small>
              </td>

              <td>
                <span class="badge badge-primary">{{ $key->nombre_modelo }}</span>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
   @endcannot
</main>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // 1. OBJETO DE TRADUCCIÓN LOCAL (Adiós errores de CORS y red)
    var lenguajeEspanol = {
        "decimal": "",
        "emptyTable": "No hay información",
        "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
        "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
        "infoFiltered": "(Filtrado de _MAX_ entradas totales)",
        "infoPostFix": "",
        "thousands": ",",
        "lengthMenu": "Mostrar _MENU_ entradas",
        "loadingRecords": "Cargando...",
        "processing": "Procesando...",
        "search": "Buscar:",
        "zeroRecords": "Sin resultados encontrados",
        "paginate": {
            "first": "Primero",
            "last": "Último",
            "next": "Siguiente",
            "previous": "Anterior"
        }
    };

    // 1.2. INICIALIZACIÓN SIN BORRADO DE DATOS
    var tabla;
    try {
        // Inicialización directa. "retrieve: true" le dice que si ya existe la use, 
        // pero quitamos el .clear().destroy() para que no borre el tbody.
        tabla = $('#tabla-precios').DataTable({
            "responsive": true,
            "autoWidth": false,
            "language": lenguajeEspanol,
            "retrieve": true,
            "paging": true,
            "searching": true
        });
    } catch (e) {
        console.log("Error en DataTable: ", e);
    }
    // 2. Click para editar
    $('#tabla-precios').on('click', '.celda-editable', function(e) {
        if ($(e.target).is('input, button, i')) return;
        
        $('.contenedor-edicion').addClass('d-none');
        $('.costo-texto').removeClass('d-none');

        $(this).find('.costo-texto').addClass('d-none');
        $(this).find('.contenedor-edicion').removeClass('d-none');
        $(this).find('.input-costo').focus().select();
    });

    // 3. Cálculo en Tiempo Real (Lógica de tu modelo de venta)
    $('#tabla-precios').on('input', '.input-costo', function() {
        let input = $(this);
        let fila = input.closest('tr');
        let nuevoCosto = parseFloat(input.val());

        if (isNaN(nuevoCosto) || nuevoCosto < 0) return;

        // Extraer factores del data-attribute de la fila
        let tBcv = parseFloat(fila.data('tasa-bcv'));
        let tBinance = parseFloat(fila.data('tasa-binance'));
        let fBcv = parseFloat(fila.data('factor-bcv'));
        let fUsdt = parseFloat(fila.data('factor-usdt'));

        // Fórmulas originales de tu sistema
        let vUsdBcv = (fBcv > 0) ? ((tBinance / tBcv) / fBcv) * nuevoCosto : nuevoCosto;
        let vUsdt = (fUsdt > 0) ? nuevoCosto / fUsdt : nuevoCosto;
        let vBs = vUsdBcv * tBcv;

        // Actualizar visualmente con color naranja para indicar cambio pendiente
        fila.find('.col-venta-usd').text('$' + vUsdBcv.toFixed(2)).css('color', '#e67e22');
        fila.find('.col-venta-bs').html(vBs.toFixed(2) + ' <small>Bs</small>').css('color', '#e67e22');
        fila.find('.col-venta-usdt').html(vUsdt.toFixed(2) + ' <small>USDT</small>').css('color', '#e67e22');
    });

    // 5. Botón Guardar (AJAX)
$('#tabla-precios').on('click', '.btn-guardar', function(e) {
    e.stopPropagation();
    let btn = $(this);
    let celda = btn.closest('.celda-editable');
    let fila = btn.closest('tr');
    let idInsumo = fila.data('id'); // O celda.data('id') según tu estructura
    let nuevoCosto = celda.find('.input-costo').val();

    // Bloqueamos para evitar doble clic
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

    $.ajax({
        url: "{{ route('insumos.actualizarCosto') }}",
        method: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            id: idInsumo,
            costo: nuevoCosto
        },
        success: function(response) {
            if(response.success) {
                // 1. Actualizamos el texto del costo
                celda.find('.costo-texto').text('$' + parseFloat(nuevoCosto).toFixed(2)).removeClass('d-none');
                
                // 2. Escondemos el input y botones
                celda.find('.contenedor-edicion').addClass('d-none');
                
                // 3. ¡IMPORTANTE! Reseteamos el botón para la próxima vez
                btn.prop('disabled', false).html('<i class="fa fa-check"></i>');
                
                // 4. Quitamos el color naranja de los cálculos
                fila.find('.col-venta-usd').css('color', '#1e8449');
                fila.find('.col-venta-bs').css('color', '#21618c');
                fila.find('.col-venta-usdt').css('color', '#a04000');
                
                // 5. Refrescamos DataTables para que reconozca los nuevos valores
                tabla.row(fila).invalidate().draw(false);
            }
        },
        error: function(xhr) {
            // Si hay error, también debemos habilitar el botón para reintentar
            btn.prop('disabled', false).html('<i class="fa fa-check"></i>');
            alert('Error al guardar: ' + xhr.responseText);
        }
    });
});
//5.1 guardar con enter
$('#tabla-precios').on('keypress', '.input-costo', function(e) {
    if(e.which == 13) { // 13 es la tecla Enter
        $(this).closest('.celda-editable').find('.btn-guardar').click();
    }
});
    // 6. Cancelar
    $('#tabla-precios').on('click', '.btn-cancelar', function(e) {
        e.stopPropagation();
        location.reload(); // Forma más segura de resetear cálculos visuales
    });
});
</script>

<style>
    .text-orange { color: #e67e22 !important; }
    .contenedor-edicion { min-width: 120px; }
</style>
@endsection