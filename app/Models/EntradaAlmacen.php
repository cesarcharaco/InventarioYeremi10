<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EntradaAlmacen extends Model
{
    protected $table = 'entradas_almacen';

    protected $fillable = [
        'id_proveedor',
        'id_local',
        'id_user',
        'nro_orden_entrega',
        'fecha_entrada',
        'total_costo_usd',
        'observaciones'
    ];

    // Relación con el Proveedor
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }

    // Relación con el Local (Sede donde entró la mercancía)
    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class, 'id_local');
    }

    // Relación con el Usuario que recibió
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // Relación con los productos específicos de esta entrada
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleEntrada::class, 'id_entrada');
    }
}
