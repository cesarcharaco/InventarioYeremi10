<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected $fillable = [
        'nombre',
        'rif',
        'telefono',
        'email',
        'direccion'
    ];

    // Relación: Un proveedor tiene muchas entradas de almacén
    public function entradas(): HasMany
    {
        return $this->hasMany(EntradaAlmacen::class, 'id_proveedor');
    }
}
