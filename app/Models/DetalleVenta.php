<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleVenta extends Model
{
    protected $table = 'detalle_ventas';

    protected $fillable = [
        'id_venta',
        'id_insumo',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'promocion_regla_id',
        'precio_unitario',
        'porcentaje_descuento_aplicado'
    ];

    // Relación con la venta cabecera
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'id_venta');
    }

    // Relación con el producto/insumo
    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumos::class, 'id_insumo');
    }

    public function promocionRegla()
    {
        return $this->belongsTo(PromocionRegla::class, 'promocion_regla_id');
    }
}