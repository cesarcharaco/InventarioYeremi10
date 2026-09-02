<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsumoRecepcion extends Model
{
    protected $table = 'insumos_recepcion';

    protected $fillable = [
        'id_detalle_entrada',
        'id_insumo',
        'id_local',
        'cantidad',
        'costo_unitario_usd',
        'origen',
        'estado',
        'observacion_recepcion'
    ];

    public function detalleEntrada(): BelongsTo
    {
        return $this->belongsTo(DetalleEntrada::class, 'id_detalle_entrada');
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumos::class, 'id_insumo');
    }

    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class, 'id_local');
    }
}