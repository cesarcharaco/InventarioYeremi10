<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $fillable = [
        'user_id', 
        'listas_oferta_id', 
        'total', 
        'estado', 
        'observaciones',
        'nro_guia',
        'transporte',
        'fecha_despacho',
        'fecha_entrega',
        'obs_entrega'
    ];

    // Relaciones
    public function detalles()
    {
        return $this->hasMany(PedidoDetalle::class, 'pedido_id');
    }
    
    public function cliente() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function listaOferta() {
        return $this->belongsTo(ListasOferta::class, 'listas_oferta_id');
    }

    /**
     * Determina si el cliente aún puede editar el pedido.
     * Según nuestro workflow, se bloquea al entrar "EN PREPARACIÓN".
     */
    public function esEditablePorCliente()
    {
        return in_array($this->estado, ['PENDIENTE', 'APROBADO']);
    }

    /**
     * Recalcula el total del pedido sumando sus detalles.
     */
    public function recalcularTotal()
    {
        $nuevoTotal = $this->detalles->sum(function($detalle) {
            return $detalle->subtotal;
        });

        $this->update(['total' => $nuevoTotal]);
    }
}