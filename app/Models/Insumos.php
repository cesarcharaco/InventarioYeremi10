<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Insumos extends Model
{
    protected $table = 'insumos';

    protected $fillable = [
        'serial',
        'producto',
        'descripcion',
        'stock_min', // Agregado: Regla de stock mínimo
        'stock_max', // Agregado: Regla de stock máximo
        'estado',// 'En Venta', 'Suspendido', 'No Disponible'
        'costo',
        'precio_venta_usd',
        'precio_venta_bs',
        'precio_venta_usdt',
        'categoria_id',
        'modelo_venta_id'
    ];

    /**
     * Relación con la Categoría
     */
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    /**
     * Relación con el Modelo de Venta
     */
    public function modeloVenta()
    {
        return $this->belongsTo(ModeloVenta::class, 'modelo_venta_id');
    }

    /**
     * Relación con las existencias físicas en diferentes locales/depósitos
     * Esta relación apunta a la tabla pivote que ahora solo tiene 'cantidad'
     */
    public function existencias()
    {
        return $this->hasMany(InsumosC::class, 'id_insumo', 'id');
    }

    /**
     * Accesor útil para obtener el stock total sumando todos los locales/depósitos
     */
    public function getStockTotalAttribute()
    {
        return $this->existencias()->sum('cantidad');
    }
    // Ver todas las incidencias de este producto
    public function incidencias(): HasMany
    {
        return $this->hasMany(Incidencias::class, 'id_insumo');
    }

    // Relación con detalles de despacho
    public function detallesDespacho()
    {
        return $this->hasMany(DespachoDetalles::class, 'id_insumo');
    }
}