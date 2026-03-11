<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditoInteres extends Model
{
    protected $table = 'credito_intereses';

    protected $fillable = [
        'id_credito',
        'id_user',
        'porcentaje',
        'monto_interes',
        'saldo_anterior',
        'saldo_nuevo',
        'observacion',
        'aplicado_en',
        'estado'
    ];

    // Para tratar 'aplicado_en' como una instancia de Carbon automáticamente
    protected $casts = [
        'aplicado_en' => 'datetime',
    ];

    public $timestamps = false; // Ya que usas 'aplicado_en' manualmente

    /**
     * El crédito al que se le aplicó el interés.
     */
    public function credito()
    {
        return $this->belongsTo(Credito::class, 'id_credito');
    }

    /**
     * El administrador que realizó la acción.
     */
    public function administrador()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * Retorna el monto del interés formateado para visualización rápida
     */
    public function getMontoInteresFormateadoAttribute()
    {
        return '$' . number_format($this->monto_interes, 2);
    }

    /**
     * Verifica si el registro de interés es financieramente consistente
     */
    public function esConsistente()
    {
        // Tolerancia mínima por temas de redondeo (ej. 0.001)
        return abs(($this->saldo_anterior + $this->monto_interes) - $this->saldo_nuevo) < 0.01;
    }

    /**
     * Scope para filtrar por fecha (útil para reportes mensuales)
     */
    public function scopeEnMes($query, $mes, $año)
    {
        return $query->whereMonth('aplicado_en', $mes)
                     ->whereYear('aplicado_en', $año);
    }
}