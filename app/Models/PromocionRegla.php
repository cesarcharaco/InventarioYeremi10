<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromocionRegla extends Model
{
    use HasFactory;

    protected $table = 'promociones_reglas';

    protected $fillable = [
        'local_id',
        'nombre',
        'alcance',
        'referencia_id',
        'porcentaje_descuento',
        'fecha_inicio',
        'fecha_fin',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'porcentaje_descuento' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    /**
     * Relación con el local al que pertenece la regla de promoción.
     */
    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class, 'local_id');
    }

    /**
     * Relación polimórfica dinámica para cargar Categoría o Insumos[cite: 6, 8].
     */
    public function referencia()
    {
        return $this->morphTo(__FUNCTION__, 'alcance', 'referencia_id');
    }

    /**
     * Relación con los detalles de venta que aplicaron esta regla de promoción.
     */
    public function detalleVentas()
    {
        return $this->hasMany(DetalleVenta::class, 'promocion_regla_id');
    }
}