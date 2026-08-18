<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Correlativo extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_factura',
        'numero_control',
        'estado',
        'venta_id',
        'fecha_uso'
    ];

    protected $casts = [
        'fecha_uso' => 'datetime',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }
}