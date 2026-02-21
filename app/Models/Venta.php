<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    protected $table = 'ventas';

    protected $fillable = [
        'codigo_factura',
        'id_cliente',
        'id_user',
        'id_local',
        'id_caja',
        'pago_usd_efectivo',
        'pago_bs_efectivo',
        'pago_punto_bs',      // Incluye: Puntos y Biopagos
        'pago_pagomovil_bs',  // Incluye: Pago móvil y Transferencias
        'monto_credito_usd',
        'total_usd',
        'estado'
    ];

    // --- RELACIONES ---

    /**
     * Una venta tiene muchos detalles (artículos vendidos)
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'id_venta');
    }

    /**
     * La venta pertenece a un cliente
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    /**
     * La venta fue realizada por un usuario
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * La venta se realizó en un local específico
     */
    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class, 'id_local');
    }

    /**
     * La venta está asociada a una apertura de caja
     */
    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'id_caja');
    }

    public function credito()
    {
        return $this->hasOne(Credito::class, 'id_venta');
    }
}