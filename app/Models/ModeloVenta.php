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
        // 1. Aplicar primero el porcentaje extra / margen si existe ($2.00 * 1.10 = $2.20)
        $costoBase = ($this->porcentaje_extra > 0) 
            ? $costo * (1 + $this->porcentaje_extra) 
            : $costo;

        // 2. LÓGICA PARA VENTA BCV / BOLÍVARES
        if ($this->factor_bcv > 0) {
            // Aplica fórmula de protección cambiaria sobre el costo con margen
            $diferencial = ($this->tasa_bcv > 0) ? ($this->tasa_binance / $this->tasa_bcv) : 1;
            $venta_bcv = ($diferencial / $this->factor_bcv) * $costoBase;
        } else {
            $venta_bcv = $costoBase;
        }

        // 3. LÓGICA PARA VENTA USDT
        if ($this->factor_usdt > 0) {
            $venta_usdt = $costoBase / $this->factor_usdt;
        } else {
            $venta_usdt = $costoBase;
        }

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

    public function historicosInsumosRecepcion()
    {
        return $this->hasMany(HistoricoInsumoRecepcion::class, 'id_modelo_venta_anterior');
    }
}