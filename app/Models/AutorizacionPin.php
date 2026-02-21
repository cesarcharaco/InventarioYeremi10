<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutorizacionPin extends Model
{
    // Definimos la tabla (importante porque el plural en español es complejo para Laravel)
    protected $table = 'autorizacion_pines';

    protected $fillable = [
        'id_local',
        'pin',
        'monto',
        'vendedor',
        'cliente',
        'estado'
    ];

    // Relación: El PIN pertenece a un Local
    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class, 'id_local');
    }
}