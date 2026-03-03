<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class VentaInformacion extends Model
{
    protected $table = 'ventas_info_adicional';
    protected $fillable = [
        'id_venta', 'tipo_documento', 'correlativo_nota', 
        'porcentaje_descuento', 'monto_descuento_usd', 
        'base_imponible_bs', 'iva_bs', 'aplica_abono'
    ];

    public function venta() {
        return $this->belongsTo(Venta::class, 'id_venta');
    }
}
