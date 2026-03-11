<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Credito extends Model
{
    protected $table = 'creditos';

    protected $fillable = [
        'id_venta',
        'id_cliente',
        'monto_inicial',
        'saldo_pendiente',
        'fecha_vencimiento',
        'estado',
        'saldo_a_favor',
        'ultima_revalorizacion',
        'tasa_cambio_origen'
    ];

    // Casts para manejar fechas automáticamente
    protected $casts = [
        'fecha_vencimiento' => 'date',
        'ultima_revalorizacion' => 'datetime',
    ];

    // --- RELACIONES ---

    /**
     * El crédito se origina de una venta específica.
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'id_venta');
    }

    /**
     * El crédito pertenece a un cliente.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    /**
     * Un crédito tiene muchos abonos realizados a lo largo del tiempo.
     */
    public function abonos(): HasMany
    {
        return $this->hasMany(AbonoCredito::class, 'id_credito');
    }

    // --- LÓGICA DE APOYO (Opcional pero recomendada) ---

    /**
     * Atributo para saber cuánto se ha pagado en total (Monto inicial - Saldo pendiente)
     */
    public function getMontoPagadoAttribute()
    {
        return $this->monto_inicial - $this->saldo_pendiente;
    }

    public function intereses()
    {
        return $this->hasMany(CreditoInteres::class, 'id_credito');
    }

    // --- LÓGICA DE APOYO ---

    /**
     * Suma total de todos los intereses registrados históricamente
     */
    public function getTotalInteresesAttribute()
    {
        // Solo sumamos los que están 'aplicado'
        return $this->intereses()->where('estado', 'aplicado')->sum('monto_interes');
    }

    /**
     * El monto que el cliente debería hoy si no hubiera pagado nada (Capital + Intereses)
     */
    public function getMontoTotalActualizadoAttribute()
    {
        return $this->monto_inicial + $this->total_intereses;
    }

    public function egresosRelacionados() {
        return $this->hasMany(CajaMovimiento::class, 'id_credito');
    }

    public function getEstadoAttribute() {
        return $this->saldo_pendiente <= 0 ? 'pagado' : 'pendiente';
    }

    public function scopeConSaldoCalculado($query) {
        // Esto permite que el ORM use la lógica de tu servicio
        // al momento de hacer la consulta.
        return $query->withSum(['abonos as total_abonos' => function($q) {
            $q->where('estado', 'Realizado');
        }], 'monto_pagado_usd');
    }
}