<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListasOferta extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre', 'proveedor', 'fecha_inicio', 'fecha_fin', 'monto_minimo','incremento', 'estado'
    ];

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'listas_oferta_id');
    }

    public function items()
    {
        // Relación con los productos que pertenecen a esta lista
        return $this->hasMany(InsumosMayor::class, 'lista_oferta_id');
    }
}
