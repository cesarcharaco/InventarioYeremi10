<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Despachos extends Model
{
    protected $table = 'despachos';

    protected $fillable = [
        'codigo',
        'id_local_origen',
        'id_local_destino',
        'transportado_por',
        'vehiculo_placa',
        'observacion',
        'estado',
        'fecha_despacho',
        'fecha_recepcion'
    ];

    // Relación con el local de origen
    public function origen()
    {
        return $this->belongsTo(Local::class, 'id_local_origen');
    }

    // Relación con el local de destino
    public function destino()
    {
        return $this->belongsTo(Local::class, 'id_local_destino');
    }

    // Relación con los productos del despacho
    public function detalles() {
        return $this->hasMany(\App\Models\DespachoDetalles::class, 'id_despacho');
    }
}