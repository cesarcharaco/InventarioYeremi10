<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    use HasFactory;

    protected $table = 'cajas';

    // Mantenemos consistencia total con tu migración
    protected $fillable = [
        'id_user',
        'id_local',
        'monto_apertura_usd',
        'monto_apertura_bs',
        'fecha_apertura',
        'monto_cierre_usd_efectivo',
        'monto_cierre_bs_efectivo',
        'monto_cierre_punto',
        'monto_cierre_pagomovil',
        'reportado_cierre_usd_efectivo',
        'reportado_cierre_bs_efectivo',
        'reportado_cierre_punto',
        'fecha_cierre',
        'estado'
    ];

    // Casts para asegurar que las fechas sean objetos Carbon y los montos sean numéricos
    protected $casts = [
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
        'monto_apertura_usd' => 'decimal:2',
        'monto_cierre_usd_efectivo' => 'decimal:2',
        'monto_cierre_bs_efectivo' => 'decimal:2',
        'monto_cierre_punto' => 'decimal:2',
        'monto_cierre_pagomovil' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones (Relationships)
    |--------------------------------------------------------------------------
    */

    // El vendedor que abrió la caja
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // El local (sede) donde opera esta caja
    public function local()
    {
        return $this->belongsTo(Local::class, 'id_local');
    }

    // Ventas realizadas en esta jornada de caja
    public function ventas()
    {
        return $this->hasMany(Venta::class, 'id_caja');
    }

    // Abonos de créditos recibidos en esta jornada
    public function abonos()
    {
        return $this->hasMany(AbonoCredito::class, 'id_caja');
    }

}