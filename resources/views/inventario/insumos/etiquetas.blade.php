@extends('layouts.app')

@section('title') Carrito de Etiquetas @endsection

@section('content')
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-barcode"></i> Generador de Etiquetas por Lote</h1>
      <p>Busca e incluye insumos a la cola de impresión</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('insumos.index') }}">Insumos</a></li>
      <li class="breadcrumb-item">Etiquetas</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <h3 class="tile-title">1. Buscar Insumo</h3>
        <div class="form-group position-relative">
          <input type="text" id="buscador" class="form-control form-control-lg" placeholder="Escribe el serial o nombre del insumo (Ej: SERG-00001)..." autocomplete="off">
          <div id="resultados-busqueda" class="list-group position-absolute w-100 shadow" style="z-index: 1000; display:none;"></div>
        </div>

        <form action="{{ route('insumos.barcode_pdf_multiple') }}" method="POST" target="_blank" id="form-carrito">
          @csrf
          <h3 class="tile-title mt-4">2. Cola de Impresión</h3>
          
          <table class="table table-bordered table-striped" id="tabla-carrito">
            <thead class="bg-primary text-white">
              <tr>
                <th>Serial</th>
                <th>Producto / Descripción</th>
                <th width="180px" class="text-center">Hojas Completas (24 stickers/c.u)</th>
                <th width="80px" class="text-center">Acción</th>
              </tr>
            </thead>
            <tbody id="tbody-carrito">
              <tr id="row-vacio">
                <td colspan="4" class="text-center text-muted">No has agregado ningún insumo a la cola.</td>
              </tr>
            </tbody>
          </table>

          <div class="tile-footer">
            <button class="btn btn-success icon-btn" type="submit" id="btn-submit" disabled>
              <i class="fa fa-print"></i> Generar PDF de Etiquetas
            </button>
            <a href="{{ route('insumos.index') }}" class="btn btn-secondary">Volver</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script>
let carrito = {};

$(document).ready(function() {
    // Autocompletado manual en el buscador
    $('#buscador').on('keyup', function() {
        let q = $(this).val();
        if(q.length < 2) {
            $('#resultados-busqueda').hide();
            return;
        }

        $.get("{{ route('insumos.buscar_ajax') }}", { q: q }, function(data) {
            let html = '';
            if(data.length === 0) {
                html = '<div class="list-group-item">No se encontraron productos</div>';
            } else {
                data.forEach(item => {
                    html += `<a href="#" class="list-group-item list-group-item-action" onclick="agregarAlCarrito(${item.id}, '${item.serial}', '${item.producto}', '${item.descripcion}'); return false;">
                                <strong>[${item.serial}]</strong> ${item.producto} - ${item.descripcion}
                             </a>`;
                });
            }
            $('#resultados-busqueda').html(html).show();
        });
    });
});

function agregarAlCarrito(id, serial, producto, descripcion) {
    $('#resultados-busqueda').hide();
    $('#buscador').val('');

    if (carrito[id]) {
        carrito[id].hojas += 1;
        $(`#hojas-${id}`).val(carrito[id].hojas);
        return;
    }

    carrito[id] = { id: id, serial: serial, producto: producto, descripcion: descripcion, hojas: 1 };
    renderTabla();
}

function eliminarDelCarrito(id) {
    delete carrito[id];
    renderTabla();
}

function actualizarHojas(id, valor) {
    if (carrito[id]) {
        carrito[id].hojas = parseInt(valor) || 1;
    }
}

function renderTabla() {
    let keys = Object.keys(carrito);
    if (keys.length === 0) {
        $('#tbody-carrito').html('<tr id="row-vacio"><td colspan="4" class="text-center text-muted">No has agregado ningún insumo a la cola.</td></tr>');
        $('#btn-submit').prop('disabled', true);
        return;
    }

    let html = '';
    keys.forEach((id, index) => {
        let item = carrito[id];
        html += `
            <tr>
                <td><span class="badge badge-info">${item.serial}</span></td>
                <td><strong>${item.producto}</strong>: ${item.descripcion}</td>
                <td>
                    <input type="number" class="form-control text-center" min="1" value="${item.hojas}" id="hojas-${id}" onchange="actualizarHojas(${id}, this.value)" name="items[${index}][hojas]">
                    <input type="hidden" name="items[${index}][id]" value="${item.id}">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarDelCarrito(${id})"><i class="fa fa-trash"></i></button>
                </td>
            </tr>
        `;
    });

    $('#tbody-carrito').html(html);
    $('#btn-submit').prop('disabled', false);
}
</script>
@endsection