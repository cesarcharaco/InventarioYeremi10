<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsumoFoto extends Model
{
    protected $table = 'insumo_fotos';

    protected $fillable = [
        'insumo_id',
        'ruta',
        'titulo',
        'es_principal'
    ];

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumos::class, 'insumo_id');
    }
}