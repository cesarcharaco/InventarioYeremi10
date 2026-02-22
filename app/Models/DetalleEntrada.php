<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleEntrada extends Model
{
    protected $table = 'detalles_entradas';

    protected $fillable = [
        'id_entrada',
        'id_insumo',
        'cantidad',
        'costo_unitario_usd'
    ];

    // Relación con la cabecera de la entrada
    public function entrada(): BelongsTo
    {
        return $this->belongsTo(EntradaAlmacen::class, 'id_entrada');
    }

    // Relación con el Insumo (Producto)
    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class, 'id_insumo');
    }
}