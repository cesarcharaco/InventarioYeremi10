@extends('layouts.app')

@section('title') Cargar Correlativos @endsection

@section('content')
<main class="app-content">
    <div class="app-title">
        <div>
            <h1><i class="fa fa-plus"></i> Registrar Correlativos Fiscales</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="tile">
                <div class="tile-body">
                    <form action="{{ route('correlativos.store') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label><strong>Modo de Carga:</strong></label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="modo_lote" id="modo_individual" value="0" checked onclick="toggleModo(false)">
                                <label class="form-check-label" for="modo_individual">Individual</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="modo_lote" id="modo_lote_val" value="1" onclick="toggleModo(true)">
                                <label class="form-check-label" for="modo_lote_val">Carga en Lote / Rango</label>
                            </div>
                        </div>

                        <hr>

                        {{-- Campos Individuales --}}
                        <div id="seccion_individual">
                            <div class="form-group">
                                <label>Número de Factura:</label>
                                <input type="text" name="numero_factura" class="form-control" placeholder="Ej: 000136">
                            </div>
                            <div class="form-group">
                                <label>Número de Control:</label>
                                <input type="text" name="numero_control" class="form-control" placeholder="Ej: 00-000136">
                            </div>
                        </div>

                        {{-- Campos por Lote --}}
                        <div id="seccion_lote" style="display: none;">
                            <div class="form-group">
                                <label>Prefijo del N° de Control:</label>
                                <input type="text" name="prefijo_control" class="form-control" value="00-">
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Desde (Número inicial):</label>
                                        <input type="number" name="desde" class="form-control" placeholder="Ej: 136">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Hasta (Número final):</label>
                                        <input type="number" name="hasta" class="form-control" placeholder="Ej: 180">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tile-footer">
                            <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Guardar</button>
                            <a href="{{ route('correlativos.index') }}" class="btn btn-secondary"><i class="fa fa-times"></i> Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    function toggleModo(esLote) {
        document.getElementById('seccion_individual').style.display = esLote ? 'none' : 'block';
        document.getElementById('seccion_lote').style.display = esLote ? 'block' : 'none';
    }
</script>
@endsection