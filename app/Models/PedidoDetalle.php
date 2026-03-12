<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoDetalle extends Model
{
    protected $table = 'pedido_detalles';

    protected $fillable = [
        'pedido_id', 
        'insumos_mayores_id', 
        'cantidad_solicitada', 
        'cantidad_despachada', 
        'precio_unitario'
    ];

    // Relaciones
    public function pedido() {
        return $this->belongsTo(Pedido::class);
    }

    public function producto() {
        return $this->belongsTo(InsumosMayor::class, 'insumos_mayores_id');
    }

    /**
     * Calcula el subtotal real basado en el estado del pedido.
     * Si está en despacho, usa la cantidad despachada; si no, la solicitada.
     */
    public function getSubtotalAttribute()
    {
        $cantidad = ($this->pedido->estado === 'PENDIENTE' || $this->pedido->estado === 'APROBADO') 
                    ? $this->cantidad_solicitada 
                    : $this->cantidad_despachada;

        return $cantidad * $this->precio_unitario;
    }

    
}