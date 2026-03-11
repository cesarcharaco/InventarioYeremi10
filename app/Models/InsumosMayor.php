<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsumosMayor extends Model
{
    use HasFactory;

    protected $table = 'insumos_mayores';

    protected $fillable = [
        'lista_oferta_id',
        'codigo',
        'descripcion',
        'aplicativo',
        'costo_usd',
        'venta_usd',
        'estado'
    ];

    public function listaOferta()
    {
        return $this->belongsTo(ListasOferta::class, 'lista_oferta_id');
    }

    public function detallesPedido()
    {
        return $this->hasMany(PedidoDetalle::class, 'insumos_mayor_id');
    }
}
