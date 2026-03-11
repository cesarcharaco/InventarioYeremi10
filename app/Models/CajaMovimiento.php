<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CajaMovimiento extends Model
{
    protected $table = 'caja_movimientos';

    protected $fillable = [
        'id_caja',
        'id_user',
        'id_credito',
        'tipo',
        'categoria',
        'efectivo_bs', // Nuevo campo
        'efectivo_usd', // Nuevo campo
        'observacion',
    ];

    // Relaciones
    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'id_caja');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function credito(): BelongsTo
    {
        return $this->belongsTo(Credito::class, 'id_credito');
    }
}