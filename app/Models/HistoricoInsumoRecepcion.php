<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoInsumoRecepcion extends Model
{
    use HasFactory;

    protected $table = 'historico_insumos_recepcion';

    protected $fillable = [
        'id_detalle_entrada',
        'id_insumo',
        'costo_anterior',
        'id_modelo_venta_anterior',
    ];

    // Relación con el detalle de la entrada
    public function detalleEntrada()
    {
        return $this->belongsTo(DetalleEntrada::class, 'id_detalle_entrada');
    }

    // Relación con el insumo maestro (ajusta el nombre de la clase si tu modelo se llama Insumo o Insumos)
    public function insumo()
    {
        return $this->belongsTo(Insumos::class, 'id_insumo');
    }

    // Relación con el modelo de venta anterior
    public function modeloVentaAnterior()
    {
        return $this->belongsTo(ModeloVenta::class, 'id_modelo_venta_anterior');
    }
}