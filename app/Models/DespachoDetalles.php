<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DespachoDetalles extends Model
{
    protected $table = 'despacho_detalles';

    protected $fillable = [
        'id_despacho',
        'id_insumo',
        'cantidad_enviada',  // <-- Reemplaza al antiguo 'cantidad'
        'cantidad_recibida'  // <-- Nuevo campo para registrar lo que llegó físicamente
    ];

    // Relación con el despacho padre
    public function despacho()
    {
        return $this->belongsTo(Despachos::class, 'id_despacho');
    }

    public function insumos() {
        return $this->belongsTo(\App\Models\Insumos::class, 'id_insumo');
    }
}