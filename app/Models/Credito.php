<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Credito extends Model
{
    protected $table = 'creditos';

    protected $fillable = [
        'id_venta',
        'id_cliente',
        'monto_inicial',
        'saldo_pendiente',
        'fecha_vencimiento',
        'estado',
        'ultima_revalorizacion',
        'tasa_cambio_origen'
    ];

    // Casts para manejar fechas automáticamente
    protected $casts = [
        'fecha_vencimiento' => 'date',
        'ultima_revalorizacion' => 'datetime',
    ];

    // --- RELACIONES ---

    /**
     * El crédito se origina de una venta específica.
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'id_venta');
    }

    /**
     * El crédito pertenece a un cliente.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    /**
     * Un crédito tiene muchos abonos realizados a lo largo del tiempo.
     */
    public function abonos(): HasMany
    {
        return $this->hasMany(AbonoCredito::class, 'id_credito');
    }

    // --- LÓGICA DE APOYO (Opcional pero recomendada) ---

    /**
     * Atributo para saber cuánto se ha pagado en total (Monto inicial - Saldo pendiente)
     */
    public function getMontoPagadoAttribute()
    {
        return $this->monto_inicial - $this->saldo_pendiente;
    }
}