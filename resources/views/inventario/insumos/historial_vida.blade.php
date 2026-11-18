@extends('layouts.app')

@section('title') Historial de Vida del Insumo @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-history"></i> Historial de Vida del Insumo</h1>
      <p>Trazabilidad operativa completa: Entradas, Traslados y Ajustes (Excluyendo Ventas)</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item">Inventario</li>
      <li class="breadcrumb-item active">Historial de Vida</li>
    </ul>
  </div>

  {{-- BARRA DE SELECCIÓN Y FILTROS --}}
  <div class="row mb-4">
    <div class="col-md-12">
      <div class="tile shadow-sm p-3">
        <h5 class="tile-title text-primary mb-3"><i class="fa fa-search"></i> Seleccionar Repuesto</h5>
        <div class="row align-items-end">
          <div class="col-md-5 form-group mb-md-0 position-relative">
            <label for="buscador_historial"><b>Buscar Insumo / Repuesto:</b></label>
            <input type="text" id="buscador_historial" class="form-control" placeholder="Escribe el serial o nombre del insumo..." autocomplete="off">
            <input type="hidden" id="select_insumo_historial" value="">
            <div id="resultados-busqueda-historial" class="list-group position-absolute w-100 shadow" style="z-index: 1000; display:none; max-height: 300px; overflow-y: auto;"></div>
          </div>
          <div class="col-md-2 form-group mb-md-0">
            <label for="filtro_desde">Fecha Desde:</label>
            <input type="date" id="filtro_desde" class="form-control form-control-sm" max="{{ date('Y-m-d') }}">
          </div>
          <div class="col-md-2 form-group mb-md-0">
            <label for="filtro_hasta">Fecha Hasta:</label>
            <input type="date" id="filtro_hasta" class="form-control form-control-sm" max="{{ date('Y-m-d') }}">
          </div>
          <div class="col-md-3 form-group mb-md-0 d-flex align-items-end">
            <button type="button" id="btn-consultar" class="btn btn-primary btn-sm w-100 mr-2" disabled>
              <i class="fa fa-eye"></i> Ver Historial
            </button>
            <button type="button" id="btn-limpiar" class="btn btn-secondary btn-sm w-100">
              <i class="fa fa-undo"></i> Limpiar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- CONTENEDOR DE INFORMACIÓN Y TIMELINE (Oculto por defecto hasta seleccionar insumo) --}}
  <div id="seccion-resultado" style="display: none;">
    
    {{-- FICHA TÉCNICA DEL INSUMO --}}
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="tile shadow-sm border-left-primary py-3">
          <div class="row align-items-center">
            <div class="col-md-3">
              <span class="text-muted small">SERIAL / CÓDIGO</span>
              <h5 class="font-weight-bold text-dark mb-0" id="lbl-serial">-</h5>
            </div>
            <div class="col-md-4">
              <span class="text-muted small">PRODUCTO</span>
              <h5 class="font-weight-bold text-primary mb-0" id="lbl-producto">-</h5>
            </div>
            <div class="col-md-2 text-md-center">
              <span class="text-muted small">STOCK GLOBAL</span>
              <h5 class="font-weight-bold text-success mb-0" id="lbl-stock-global">0 unid.</h5>
            </div>
            <div class="col-md-3 text-md-right">
              <span class="text-muted small">ESTADO GLOBAL</span>
              <div id="lbl-estado">-</div>
            </div>
          </div>

          <hr class="my-2">

          <div class="row mb-3">
            <div class="col-md-12">
              <span class="text-muted small">DESCRIPCIÓN DETALLADA</span>
              <p class="text-secondary mb-0" id="lbl-descripcion">-</p>
            </div>
          </div>

          {{-- DISPONIBILIDAD Y ESTADO POR LOCAL / DEPÓSITO / OFICINA --}}
          <div class="border-top pt-3">
            <span class="text-muted small font-weight-bold d-block mb-2">
              <i class="fas fa-store-alt mr-1 text-primary"></i> DISPONIBILIDAD Y ESTADO POR UBICACIÓN
            </span>
            <div class="row" id="contenedor-existencias-locales">
              {{-- Tarjetas por local renderizadas vía JS --}}
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- TIMELINE VERTICAL OPERATIVO --}}
    <div class="row">
      <div class="col-md-12">
        <div class="timeline" id="timeline-movimientos">
            {{-- Renderizado dinámico vía AJAX --}}
        </div>
      </div>
    </div>

  </div>

  {{-- ESTADO VACÍO / INICIAL --}}
  <div class="row" id="estado-vacio">
    <div class="col-md-12 text-center py-5">
      <div class="tile shadow-sm py-5">
        <i class="fa fa-box-open fa-3x text-muted mb-3"></i>
        <h4 class="text-muted">Ningún insumo seleccionado</h4>
        <p class="text-muted">Utiliza el buscador superior para elegir el repuesto y auditar su ciclo de vida en el inventario.</p>
      </div>
    </div>
  </div>

</main>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        let insumosDisponiblesCache = [];

        // Buscador dinámico flotante con tarjetas enriquecidas
        $('#buscador_historial').on('keyup', function() {
            let q = $(this).val().trim();
            
            if (q.length < 2) {
                $('#resultados-busqueda-historial').hide();
                return;
            }

            $.get('{{ route("insumos.buscar_ajax") }}', { q: q }, function(data) {
                insumosDisponiblesCache = data;
                let html = '';

                if (data.length === 0) {
                    html = '<div class="list-group-item text-muted py-2">No se encontraron repuestos</div>';
                } else {
                    data.forEach(item => {
                        let categoriaNombre = item.categoria ? item.categoria.categoria : 'REPUESTO';
                        let precioUsd = item.precio_venta_usd ? `\$ ${parseFloat(item.precio_venta_usd).toFixed(2)}` : '';

                        html += `
                            <a href="#" class="list-group-item list-group-item-action py-2 px-3" onclick="seleccionarInsumoHistorial(${item.id}); return false;">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <h6 class="mb-1 font-weight-bold text-dark" style="font-size: 0.95rem;">${item.producto}</h6>
                                    ${precioUsd ? `<span class="badge badge-success px-2 py-1" style="font-size: 0.8rem;">${precioUsd}</span>` : ''}
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-primary font-weight-bold"><i class="fa fa-tag mr-1"></i> ${item.descripcion || categoriaNombre}</small>
                                    <span class="badge badge-light border text-muted" style="font-size: 0.75rem;"><i class="fa fa-barcode mr-1"></i> ${item.serial}</span>
                                </div>
                            </a>
                        `;
                    });
                }
                $('#resultados-busqueda-historial').html(html).show();
            });
        });

        // Ocultar resultados si se hace clic fuera
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#buscador_historial, #resultados-busqueda-historial').length) {
                $('#resultados-busqueda-historial').hide();
            }
        });
    });

    // Función al hacer clic en un resultado de la lista
    function seleccionarInsumoHistorial(id) {
        let buscador = $('#buscador_historial');
        let hiddenInput = $('#select_insumo_historial');

        // Hacer una pequeña consulta rápida o buscar el texto seleccionado para mostrarlo ordenado
        let url = "{{ route('insumos.historial_vida.data', ':id') }}".replace(':id', id);
        
        // Asignar ID
        hiddenInput.val(id).trigger('change');
        $('#resultados-busqueda-historial').hide();

        // Obtener datos básicos para pintar el input con el formato limpio
        $.get(url, function(res) {
            let ins = res.insumo;
            buscador.val(`${ins.serial} - ${ins.producto}: ${ins.descripcion}`);
            $('#btn-consultar').prop('disabled', false);
        });
    }

    // Modificar la limpieza para vaciar también el hidden y el nuevo buscador
    $('#btn-limpiar').on('click', function() {
        $('#buscador_historial').val('');
        $('#select_insumo_historial').val('');
        $('#filtro_desde').val('');
        $('#filtro_hasta').val('');
        $('#seccion-resultado').hide();
        $('#estado-vacio').show();
        $('#btn-consultar').prop('disabled', true);
    });

    // En el botón consultar, ahora leemos el valor del hidden input '#select_insumo_historial'
    $('#btn-consultar').off('click').on('click', function() {
        let insumoId = $('#select_insumo_historial').val();
        let desde = $('#filtro_desde').val();
        let hasta = $('#filtro_hasta').val();

        if (!insumoId) return;

        Swal.fire({
            title: 'Cargando historial...',
            text: 'Consultando operaciones de inventario.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        let url = "{{ route('insumos.historial_vida.data', ':id') }}".replace(':id', insumoId);

        $.get(url, { fecha_desde: desde, fecha_hasta: hasta }, function(response) {
            Swal.close();
            renderizarHistorial(response);
        }).fail(function() {
            Swal.fire('Error', 'No se pudo obtener el historial del insumo.', 'error');
        });
    });

    // Helper nativo para formatear fechas y horas limpias
    function formatearFechaHora(fechaStr) {
        if (!fechaStr) return 'N/D';
        let fecha = new Date(fechaStr);
        return fecha.toLocaleString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    }

    function formatearDia(fechaStr) {
        if (!fechaStr) return '';
        let fecha = new Date(fechaStr);
        return fecha.toLocaleDateString('es-ES', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    }

    function renderizarHistorial(data) {
        let insumo = data.insumo;
        let existencias = data.existencias_locales || [];
        let historial = data.historial;

        // Rellenar cabecera de la ficha técnica
        $('#lbl-serial').text(insumo.serial || 'S/N');
        $('#lbl-producto').text(insumo.producto);
        $('#lbl-descripcion').text(insumo.descripcion || 'Sin descripción registrada.');
        
        let badgeEstado = insumo.estado === 'En Venta' ? 'badge-success' : 'badge-dark';
        $('#lbl-estado').html(`<span class="badge ${badgeEstado} px-3 py-1" style="font-size: 0.85rem;"><i class="fas fa-globe mr-1"></i> ${insumo.estado}</span>`);

        // Calcular Stock Global y renderizar tarjetas por Local / Depósito / Oficina
        let stockGlobal = 0;
        let htmlExistencias = '';

        if (existencias.length === 0) {
            htmlExistencias = `
                <div class="col-12">
                    <div class="alert alert-light border mb-0 py-2 text-muted small">
                        <i class="fas fa-info-circle mr-1"></i> Este insumo aún no tiene registros de existencia asignados en ningún local o depósito.
                    </div>
                </div>
            `;
        } else {
            existencias.forEach(function(loc) {
                let cant = parseInt(loc.cantidad) || 0;
                stockGlobal += cant;

                let estadoLoc = loc.estado_local || 'Disponible';
                let esDisponible = estadoLoc === 'Disponible';
                let badgeEstadoLoc = esDisponible
                    ? '<span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i> Disponible</span>'
                    : '<span class="badge badge-danger"><i class="fas fa-ban mr-1"></i> ' + estadoLoc + '</span>';

                let iconoTipo = loc.tipo_local === 'DEPOSITO'
                    ? 'fas fa-warehouse text-info'
                    : 'fas fa-store text-primary';

                let colorCantidad = cant <= 0
                    ? 'text-danger'
                    : (cant <= (parseInt(insumo.stock_min) || 0) ? 'text-warning' : 'text-dark');

                htmlExistencias += `
                    <div class="col-md-3 col-sm-6 mb-2">
                        <div class="border rounded p-2 bg-light h-100 d-flex flex-column justify-content-between">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="font-weight-bold text-dark" style="font-size: 0.88rem;">
                                    <i class="${iconoTipo} mr-1"></i> ${loc.local_nombre}
                                </span>
                                ${badgeEstadoLoc}
                            </div>
                            <div class="d-flex justify-content-between align-items-baseline mt-1 pt-1 border-top">
                                <small class="text-muted">${loc.tipo_local || 'LOCAL'}</small>
                                <span class="font-weight-bold ${colorCantidad}" style="font-size: 1.05rem;">
                                    ${cant} <small class="text-muted font-weight-normal">unid.</small>
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            });
        }

        $('#lbl-stock-global').text(`${stockGlobal} unid.`);
        $('#contenedor-existencias-locales').html(htmlExistencias);

        // Renderizar Timeline Operativo
        let htmlTimeline = '';

        if (historial.length === 0) {
            htmlTimeline = `
                <div class="text-center py-4 bg-white rounded shadow-sm">
                    <p class="text-muted mb-0"><i class="fa fa-info-circle"></i> No se registraron movimientos para este insumo en el rango de fechas seleccionado.</p>
                </div>
            `;
        } else {
            let fechaActualGrupo = '';

            historial.forEach(function(item) {
                let fechaFormateada = formatearFechaHora(item.fecha);
                let fechaDia = formatearDia(item.fecha);

                // Etiqueta de día en el timeline si cambia la fecha
                if (fechaDia !== fechaActualGrupo) {
                    fechaActualGrupo = fechaDia;
                    htmlTimeline += `
                        <div class="time-label">
                            <span class="bg-secondary text-white px-3 py-1 rounded shadow-sm font-weight-bold" style="font-size: 0.85rem;">
                                <i class="fa fa-calendar-alt mr-1"></i> ${fechaActualGrupo}
                            </span>
                        </div>
                    `;
                }

                let signoCantidad = item.tipo_evento === 'ENTRADA' ? '+' : (item.tipo_evento === 'DESPACHO' ? '-' : '•');
                let badgeClass = item.color_badge || 'secondary';

                htmlTimeline += `
                    <div>
                        <i class="${item.icono} bg-${badgeClass}"></i>
                        <div class="timeline-item shadow-sm border">
                            <span class="time text-muted"><i class="fa fa-clock"></i> ${fechaFormateada}</span>
                            <h3 class="timeline-header font-weight-bold text-dark">
                                <span class="badge badge-${badgeClass} mr-2">${item.tipo_evento}</span>
                                Ubicación / Local: <span class="text-primary">${item.local_nombre || 'N/D'}</span>
                            </h3>
                            <div class="timeline-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <p class="mb-1 text-secondary"><strong>Detalle:</strong> ${item.descripcion_evento}</p>
                                        ${item.observacion ? `<p class="mb-1 text-muted small font-italic"><i class="fa fa-comment-alt"></i> Obs: ${item.observacion}</p>` : ''}
                                    </div>
                                    <div class="col-md-4 text-md-right border-left">
                                        <div class="text-muted small">Cantidad Operada</div>
                                        <h4 class="font-weight-bold text-dark mb-0">${signoCantidad} ${item.cantidad} unidades</h4>
                                        ${item.costo ? `<div class="text-success small font-weight-bold mt-1">Costo Unitario: $ ${parseFloat(item.costo).toFixed(2)}</div>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            // Nodo final del timeline (Cierre)
            htmlTimeline += `
                <div>
                    <i class="fa fa-flag-checkered bg-gray"></i>
                </div>
            `;
        }

        $('#timeline-movimientos').html(htmlTimeline);
        $('#estado-vacio').hide();
        $('#seccion-resultado').fadeIn();
    }
</script>
@endsection