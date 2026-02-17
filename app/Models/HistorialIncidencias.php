<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialIncidencias extends Model
{
    protected $table = 'historial_incidencias';

    protected $fillable = [
        'codigo',
        'accion',
        'datos_snapshot',
        'observacion_snapshot',
        'user_id'
    ];
    
    // ESTO ES CLAVE: Convierte el JSON de la BD a un Array de PHP automáticamente
    protected $casts = [
        'datos_snapshot' => 'array',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
