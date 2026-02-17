@extends('layouts.app')
@section('title') Incidencias - Historial @endsection
@section('content')
<main class="app-content">
  {{-- 1. PROTECCIÓN DE RUTA: Solo Admin/SuperAdmin ven el historial global --}}
  @cannot('ver-historial-total')
    <div class="tile text-center">
        <h1 class="text-danger"><i class="fa fa-lock"></i> Acceso Denegado</h1>
        <p>El historial de auditoría global solo está disponible para la gerencia.</p>
        <a href="{{ route('incidencias.index') }}" class="btn btn-primary">Volver a mis incidencias</a>
    </div>
  @else
  <div class="app-title">
    <div>
      <h1><i class="fa fa-th-list"></i> Inventario</h1>
      <p>Sistema Administrativo | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="">SAYER</a></li>
      <li class="breadcrumb-item"><a href="">Incidencias</a></li>
      <li class="breadcrumb-item"><a href="">Historial</a></li>
    </ul>
  </div>
  <div class="tile mb-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 class="mb-3 line-head">Registro Maestro de Eventos
            @can('registrar-incidencia')
                <a class="btn btn-primary icon-btn pull-right" href="{{ route('incidencias.create') }}"><i class="fa fa-plus"></i>Nueva Incidencia</a>
            @endcan
            
          </h2>
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
                      <th>Insumo</th>
                      <th>Código</th>
                      <th>Acción</th>
                      <th>Usuario</th>
                      <th>Cantidad</th>
                      <th>Fecha</th>
                      <th>Observación</th>
                      <th>Acciones</th>
                  </tr>
              </thead>
              <tbody>
                  @foreach($historial as $item)
                      @php
                          // Decodificamos el JSON si viene como string, 
                          // aunque con el Cast en el modelo Laravel ya lo hace objeto/array.
                          $datos = is_array($item->datos_snapshot) ? $item->datos_snapshot : json_decode($item->datos_snapshot, true);
                      @endphp
                      <tr>
                          {{-- Columna Insumo: Prioriza el Snapshot si la relación es NULL --}}
                          <td>
                              <strong>{{ $item->producto ?? ($datos['insumo'] ?? 'N/A') }}</strong>
                          </td>

                          {{-- Columna Código --}}
                          <td><span class="badge badge-light">{{ $item->codigo }}</span></td>

                          {{-- Columna Acción: Indica si fue Creación, Edición o Anulación --}}
                          <td>
                              @switch($item->accion)
                                  @case('creacion') <span class="text-primary"><i class="fa fa-plus"></i> Creación</span> @break
                                  @case('edicion') <span class="text-warning"><i class="fa fa-edit"></i> Edición</span> @break
                                  @case('anulacion') <span class="text-danger"><i class="fa fa-trash"></i> Anulación</span> @break
                              @endswitch
                          </td>
                          <td>
                              <span class="badge badge-dark">
                                  <i class="fa fa-user"></i> {{ $item->usuario->name ?? 'Sistema/Eliminado' }}
                              </span>
                          </td>
                          {{-- Columna Cantidad --}}
                          <td>{{ $item->cantidad ?? ($datos['cantidad'] ?? '-') }}</td>

                          {{-- Columna Fecha --}}
                          <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') }}</td>

                          {{-- Columna Observación --}}
                          <td><small>{{ $item->observacion_snapshot ?? 'Sin notas' }}</small></td>

                          {{-- Columna Acciones --}}
                          <td>
                              {{-- Preguntamos por la CAPACIDAD (Permission) no por el ROL (Admin) --}}
                              @can('anular-historial')
                                  @if($item->accion !== 'anulacion')
                                      <button type="button" 
                                              onclick="confirmarDeshacer('{{ $item->codigo }}')" 
                                              class="btn btn-sm btn-outline-danger" 
                                              title="Anular Movimiento">
                                          <i class="fa fa-undo"></i> Anular
                                      </button>
                                  @else
                                      <span class="badge badge-secondary">Anulado</span>
                                  @endif
                              @else
                                  {{-- Para usuarios sin permiso, es mejor ser discretos o mostrar un estado neutro --}}
                                  @if($item->accion === 'anulacion')
                                      <span class="badge badge-secondary">Anulado</span>
                                  @else
                                      <span class="badge badge-light">Solo Lectura</span>
                                  @endif
                              @endcan
                                                      
                            <button type="button" 
                                    class="btn btn-info btn-sm" 
                                    onclick='verDetallesSnapshot(@json($datos), "{{ $item->accion }}", "{{ $item->observacion_snapshot }}", "{{ $item->usuario->name ?? 'N/A' }}")'
                                    title="Ver Detalles">
                                <i class="fa fa-eye"></i>
                            </button>
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
  @endcannot
</main>
<!-- ver detalles  -->

<div class="bs-component">
  <div class="modal fade" id="modalDetallesSnapshot" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="false">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-eye"></i> Detalles del Evento (Auditoría)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="contenido-detalle"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
</div>
<!-- deshacer incidencia -->
<div class="bs-component">
  <div class="modal" id="deshacer_incidencia">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <form action="{{ route('deshacer_incidencia') }}" method="post" id="deshacer_incidencia_form"> 
          @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-trash"></i> Deshacer Incidencia</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body">
                <p>¿Estas seguro que desea deshacer a este incidencia?</p>
            </div>
            
            <input type="hidden" name="codigo" id="codigo"> 
            
            <div class="modal-footer">
                <button class="btn btn-danger" type="submit">Deshacer</button>
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cerrar</button>
            </div>          
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
  
  // Función para anular con SweetAlert2
  function confirmarDeshacer(codigo) {
      Swal.fire({
          title: '¿Revertir movimiento?',
          text: `Se devolverá el stock y se marcará el código ${codigo} como anulado.`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Sí, revertir stock',
          cancelButtonText: 'Cancelar'
      }).then((result) => {
          if (result.isConfirmed) {
              // Llenamos el input oculto del modal y disparamos el form
              $("#codigo").val(codigo);
              $("#deshacer_incidencia_form").submit(); 
          }
      });
  }

  // Manejo de alertas de sesión
  @if(session('success'))
    Swal.fire({
        title: '¡Éxito!',
        text: '{{ session("success") }}',
        icon: 'success',
        confirmButtonColor: '#009688'
    });
  @endif

  @if(session('error'))
    Swal.fire({
        title: 'Error',
        text: '{{ session("error") }}',
        icon: 'error',
        confirmButtonColor: '#d33'
    });
  @endif
function verDetallesSnapshot(datos, accion, observacion,usuario) {
    /*console.log("Datos recibidos:", datos); // Esto te dirá en consola si los datos llegan*/

    const titulos = {
        'creacion': 'Creación de Registro',
        'edicion': 'Edición de Datos',
        'anulacion': 'Anulación de Movimiento'
    };

    let html = `
        <table class="table table-bordered table-striped">
            <tr>
                <th>Responsable:</th>
                <td><strong>${usuario}</strong></td>
            </tr>
            <tr class="table-secondary">
                <th colspan="2" class="text-center">${titulos[accion] || 'Detalles'}</th>
            </tr>
            <tr>
                <th width="35%">Insumo:</th>
                <td>${datos.insumo || 'N/A'}</td>
            </tr>
            <tr>
                <th>Cantidad:</th>
                <td><span class="badge badge-primary">${datos.cantidad || datos.cantidad_que_habia || '0'}</span></td>
            </tr>
            <tr>
                <th>Tipo/Categoría:</th>
                <td>${datos.tipo || datos.tipo_incidencia || 'N/A'}</td>
            </tr>
            <tr>
                <th>Local/Ubicación:</th>
                <td>${datos.local || 'No especificado'}</td>
            </tr>
            <tr>
                <th>Observación:</th>
                <td>${observacion || 'Sin notas adicionales'}</td>
            </tr>
        </table>
    `;

    if(accion === 'anulacion') {
        html += `
            <div class="alert alert-danger mt-2">
                <i class="fa fa-exclamation-triangle"></i> 
                <strong>Registro Anulado:</strong> Esta información es histórica.
            </div>
        `;
    }

    // 1. Inyectamos el HTML
    $('#contenido-detalle').html(html);
    
    // 2. Quitamos cualquier rastro de aria-hidden que bloquee el modal
    $('#modalDetallesSnapshot').attr('aria-hidden', 'false');
    
    // 3. Mostramos el modal manualmente
    $('#modalDetallesSnapshot').modal('show');
}
</script>
@endsection