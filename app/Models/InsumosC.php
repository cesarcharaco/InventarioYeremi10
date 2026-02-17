<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class InsumosC extends Model
{
    // Definimos el nombre exacto de la tabla según tu migración
    protected $table = 'insumos_has_cantidades';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'id_insumo',
        'id_local',
        'cantidad' // Única columna de stock físico
    ];

    /**
     * Relación inversa con el Insumo
     */
    public function insumo()
    {
        return $this->belongsTo(Insumos::class, 'id_insumo');
    }

    /**
     * Relación con el Local o Depósito
     * Esto permitirá saber a qué ubicación pertenece esta cantidad
     */
    public function local()
    {
        return $this->belongsTo(Local::class, 'id_local');
    }
}