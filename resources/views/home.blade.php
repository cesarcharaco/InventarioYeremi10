@extends('layouts.app')
@section('title') Tablero @endsection

@section('content')
<style>
    /* Estilo Info-Box inspirado en AdminLTE 3 */
    .pin-info-box {
        display: flex;
        box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        border-radius: 0.25rem;
        background-color: #fff;
        margin-bottom: 1rem;
        min-height: 85px;
        position: relative;
        border: 1px solid #dee2e6;
        transition: transform 0.2s;
    }

    .pin-info-icon {
        border-radius: 0.25rem 0 0 0.25rem;
        align-items: center;
        display: flex;
        justify-content: center;
        width: 70px;
        background-color: #dc3545; /* Rojo Danger oficial */
        color: #fff;
    }

    .pin-info-content {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 8px 15px;
        line-height: 1.3;
        flex: 1;
        overflow: hidden;
    }

    .pin-store-name {
        text-transform: uppercase;
        font-weight: 700;
        font-size: 0.75rem;
        color: #6c757d;
        display: block;
    }

    .pin-main-number {
        font-family: 'Source Sans Pro', sans-serif;
        font-size: 1.8rem;
        font-weight: 800;
        color: #212529;
        letter-spacing: 2px;
        margin: 0;
    }

    .pin-details {
        font-size: 0.8rem;
        color: #495057;
        margin-top: 4px;
        border-top: 1px solid #ebedef;
        padding-top: 4px;
    }

    /* Animación de pulso discreta en la sombra */
    .pulse-danger-soft {
        animation: pulse-red-shadow 2.5s infinite;
    }

    @keyframes pulse-red-shadow {
        0% { box-shadow: 0 1px 3px rgba(0,0,0,.2); }
        50% { box-shadow: 0 0 12px rgba(220, 53, 69, 0.4); }
        100% { box-shadow: 0 1px 3px rgba(0,0,0,.2); }
    }
</style>

<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fa fa-dashboard"></i> Tablero</h1>
      <p>Sistema Administrativo | Yermotos Repuestos C.A.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ url('home') }}">Tablero</a></li>
    </ul>
  </div>

  {{-- SECCIÓN DE PINES: Solo visible para administradores --}}
  @can('ver-autorizaciones')
  <div class="row mb-3">
      <div class="col-md-12">
          <div style="border-left: 5px solid #dc3545; padding-left: 15px;">
              <h4 class="text-danger" style="margin-bottom: 0;">Autorizaciones Pendientes</h4>
              <small class="text-muted">Actualización automática cada 10 segundos</small>
          </div>
      </div>
  </div>
  
  <div class="row" id="contenedor-pines-autorizacion">
      </div>
  <hr>
  @endcan

  <div class="row">
    <div class="col-md-6 col-lg-4">
      <div class="widget-small info coloured-icon"><i class="icon fa fa-sitemap fa-3x"></i>
        <div class="info">
          <h4><a href="{{ route('insumos.index') }}" style="text-decoration: none">Insumos</a></h4>
          <p><b>{{ $i }}</b></p>
        </div>
      </div>
    </div>
    
    <div class="col-md-6 col-lg-4">
      <div class="widget-small danger coloured-icon"><i class="icon fa fa-star fa-3x"></i>
        <div class="info">
          <h4><a href="{{ route('incidencias.index') }}" style="text-decoration: none">Incidencias</a></h4>
          <p><b>{{ $in }}</b></p>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-4">
      <div class="widget-small warning coloured-icon"><i class="icon fa fa-money fa-3x"></i>
        <div class="info">
          <h4>Tasas del Día</h4>
          <p style="font-size: 14px; margin-bottom: 0;">
            <b>BCV:</b> {{ number_format($tasa_bcv, 2, ',', '.') }} Bs.
          </p>
          <p style="font-size: 14px; margin-bottom: 0;">
            <b>Binance:</b> {{ number_format($tasa_binance, 2, ',', '.') }} Bs.
          </p>
        </div>
      </div>
    </div>
  </div>
</main>
@endsection

@section('scripts')
@can('ver-autorizaciones')
<script>
function cargarPinesActivos() {
    $.get("{{ route('admin.pines_activos') }}", function(pines) {
        let html = '';
        if(pines.length === 0) {
            html = '<div class="col-md-12 text-center text-muted my-3"><p><i class="fa fa-check-circle"></i> No hay solicitudes de crédito pendientes.</p></div>';
        }
        
        pines.forEach(p => {
            html += `
            <div class="col-md-4 col-sm-6 col-12">
                <div class="pin-info-box pulse-danger-soft">
                    <span class="pin-info-icon">
                        <i class="fa fa-lock fa-2x"></i>
                    </span>
                    <div class="pin-info-content">
                        <span class="pin-store-name">
                            <i class="fa fa-map-marker text-danger"></i> ${p.local_nombre}
                        </span>
                        <span class="pin-main-number">${p.pin}</span>
                        <div class="pin-details">
                            <span class="badge badge-success" style="font-size:0.8rem;">$${p.monto}</span> 
                            <strong class="ml-1">${p.vendedor}</strong>
                            <br>
                            <small class="text-muted d-inline-block text-truncate" style="max-width: 100%;">
                                Cliente: ${p.cliente}
                            </small>
                        </div>
                    </div>
                </div>
            </div>`;
        });
        $('#contenedor-pines-autorizacion').html(html);
    }).fail(function() {
        console.error("Error al sincronizar las autorizaciones.");
    });
}

// Ejecución inicial y temporizador
setInterval(cargarPinesActivos, 10000);
cargarPinesActivos();
</script>
@endcan
@endsection