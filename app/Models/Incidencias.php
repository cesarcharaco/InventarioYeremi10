<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incidencias extends Model
{
    protected $table = 'incidencias';

    // Actualizado: quitamos 'descontar' y agregamos 'id_local'
    protected $fillable = [
        'codigo',
        'id_insumo', 
        'id_local', 
        'cantidad', 
        'tipo', 
        'observacion', 
        'fecha_incidencia'
    ];

    /**
     * Relación con el Insumo (el repuesto)
     */
    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumos::class, 'id_insumo');
    }

    /**
     * Relación con el Local donde ocurrió la incidencia
     */
    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class, 'id_local');
    }

    /**
     * Relación con el historial de incidencias
     */
   /* public function historial(): HasMany
    {
        return $this->hasMany(HistorialIncidencias::class, 'id_incidencia', 'id');
    }*/
}