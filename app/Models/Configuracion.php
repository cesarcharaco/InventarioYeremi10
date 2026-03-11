<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $table = 'configuraciones';
    protected $fillable = ['clave', 'valor'];

    // Método para obtener cualquier tasa. Si no existe, devuelve 0.
    public static function getTasa($clave, $default = 0)
    {
        $config = self::where('clave', $clave)->first();
        return $config ? (float) $config->valor : $default;
    }

    // Método para actualizar/crear cualquier tasa.
    public static function setTasa($clave, $valor)
    {
        return self::updateOrCreate(
            ['clave' => $clave],
            ['valor' => (string) $valor] // Guardamos como string por consistencia
        );
    }
}