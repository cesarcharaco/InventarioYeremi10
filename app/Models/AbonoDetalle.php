<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbonoDetalle extends Model
{
    use HasFactory;

    protected $table = 'abono_detalles';

    protected $fillable = [
        'id_abono',
        'id_credito',
        'monto_aplicado_usd',
    ];

    /**
     * Pertenece al registro global/cabecera del abono
     */
    public function abono(): BelongsTo
    {
        return $this->belongsTo(AbonoCredito::class, 'id_abono');
    }

    /**
     * Pertenece al crédito específico amortizado
     */
    public function credito(): BelongsTo
    {
        return $this->belongsTo(Credito::class, 'id_credito');
    }
}