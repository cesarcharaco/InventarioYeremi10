<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbonoCredito extends Model
{
    use HasFactory;

    protected $table = 'abonos_credito';

    protected $fillable = [
        'id_cliente',
        'id_user',
        'id_caja',
        'monto_total_usd',
        'pago_usd_efectivo',
        'pago_bs_efectivo',
        'pago_punto_bs',
        'pago_pagomovil_bs',
        'detalles',
        'estado',
    ];

    /**
     * El abono pertenece a un cliente
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    /**
     * El abono fue recibido por un usuario/vendedor
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * El dinero del abono entró en una jornada de caja específica
     */
    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'id_caja');
    }

    /**
     * Distribución e imputación de montos hacia los créditos individuales
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(AbonoDetalle::class, 'id_abono');
    }
}