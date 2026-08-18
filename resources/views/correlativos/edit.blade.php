@extends('layouts.app')

@section('title') Editar Correlativo @endsection

@section('content')
<main class="app-content">
    <div class="app-title">
        <div>
            <h1><i class="fa fa-pencil"></i> Editar Correlativo #{{ $correlativo->numero_factura }}</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="tile">
                <div class="tile-body">
                    <form action="{{ route('correlativos.update', $correlativo->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label>Número de Factura:</label>
                            <input type="text" name="numero_factura" class="form-control" value="{{ $correlativo->numero_factura }}" required>
                        </div>

                        <div class="form-group">
                            <label>Número de Control:</label>
                            <input type="text" name="numero_control" class="form-control" value="{{ $correlativo->numero_control }}" required>
                        </div>

                        <div class="form-group">
                            <label>Estado:</label>
                            <select name="estado" class="form-control">
                                <option value="disponible" {{ $correlativo->estado == 'disponible' ? 'selected' : '' }}>Disponible</option>
                                <option value="usado" {{ $correlativo->estado == 'usado' ? 'selected' : '' }}>Usado</option>
                                <option value="anulado" {{ $correlativo->estado == 'anulado' ? 'selected' : '' }}>Anulado</option>
                            </select>
                        </div>

                        <div class="tile-footer">
                            <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Actualizar</button>
                            <a href="{{ route('correlativos.index') }}" class="btn btn-secondary"><i class="fa fa-times"></i> Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection