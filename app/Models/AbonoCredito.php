<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AbonoCredito extends Model
{
    use HasFactory;

    protected $table = 'abonos_creditos';

    protected $fillable = [
        'id_credito',
        'id_user',
        'id_caja',
        'monto_pagado_usd', // Total del abono expresado en USD para descontar de la deuda
        
        // Desglose para la caja (Montos brutos)
        'pago_usd_efectivo',
        'pago_bs_efectivo',
        'pago_punto_bs',
        'pago_pagomovil_bs',
        
        // Campo flexible para detalles del abono
        'detalles', // <--- Aquí reemplazamos 'referencia'
        'estado',
    ];

    // Dentro de class AbonoCredito extends Model

    /**
     * El abono pertenece a un crédito específico (la deuda que se está pagando)
     */
    public function credito(): BelongsTo
    {
        return $this->belongsTo(Credito::class, 'id_credito');
    }

    /**
     * El abono fue recibido por un usuario/vendedor
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * El dinero de este abono entró en una jornada de caja específica
     */
    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'id_caja');
    }
}
