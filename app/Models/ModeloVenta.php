<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ModeloVenta extends Model
{
    protected $table = 'modelos_venta';
    protected $fillable = [
        'modelo',
        'tasa_binance',
        'tasa_bcv',
        'factor_bcv',
        'factor_usdt',
        'porcentaje_extra'
    ];

    public function calcularPrecios($costo)
    {
        $venta_bcv = 0;
        $venta_usdt = 0;

        // --- LÓGICA PARA VENTA BCV / BOLÍVARES ---
        if ($this->factor_bcv > 0) {
            // Fórmula de protección cambiaria (Diferencial de tasas)
            $diferencial = ($this->tasa_bcv > 0) ? ($this->tasa_binance / $this->tasa_bcv) : 1;
            $venta_bcv = ($diferencial / $this->factor_bcv) * $costo;
        } elseif ($this->porcentaje_extra > 0) {
            // Margen fijo sobre el costo
            $venta_bcv = $costo * (1 + $this->porcentaje_extra);
        }

        // --- LÓGICA PARA VENTA USDT ---
        if ($this->factor_usdt > 0) {
            // Si hay un factor USDT específico
            $venta_usdt = $costo / $this->factor_usdt;
        } elseif ($this->porcentaje_extra > 0) {
            // Si es Margen Fijo, aplicamos el mismo margen al USDT
            $venta_usdt = $costo * (1 + $this->porcentaje_extra);
        } else {
            // Fail-safe: si no hay nada, la venta es el costo
            $venta_usdt = $costo;
        }

        // IMPORTANTE: Las llaves deben coincidir con lo que pides en el Controlador
        return [
            'precio_venta_usd'  => round($venta_bcv, 2),
            'precio_venta_bs'   => round($venta_bcv * $this->tasa_bcv, 2),
            'precio_venta_usdt' => round($venta_usdt, 2),
        ];
    }
    public function insumos()
    {
        // Nota: Asegúrate que el modelo se llame Insumo (singular) o Insumos (plural)
        return $this->hasMany(Insumos::class, 'modelo_venta_id');
    }
}