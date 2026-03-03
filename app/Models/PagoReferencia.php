<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoReferencia extends Model
{
    protected $table = 'pago_referencias';
    protected $fillable = ['id_venta', 'metodo', 'referencia', 'monto_bs', 'monto_usd'];

    public function venta() {
        return $this->belongsTo(Venta::class, 'id_venta');
    }
}