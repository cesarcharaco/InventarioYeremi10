<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Local extends Model
{
    protected $table = 'local';

    // Agregamos 'tipo' al fillable, que es el nuevo campo enum
    protected $fillable = ['nombre', 'tipo', 'estado'];

    /**
     * RELACIÓN CORREGIDA:
     * Un local tiene muchos registros de existencias (InsumosC)
     */
    public function existencias()
    {
        return $this->hasMany(InsumosC::class, 'id_local', 'id');
    }

    /**
     * RELACIÓN CORREGIDA:
     * Un local puede tener muchas salidas registradas
     */
    public function salidas()
    {
        return $this->hasMany(Salida::class, 'id_local', 'id');
    }

    /**
     * Relación con Usuarios (Muchos a Muchos)
     * Se mantiene según tu estructura original
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'users_has_local', 'id_local', 'id_user')
                    ->withPivot('status');
    }

    // Relación con las incidencias ocurridas en este local
    public function incidencias(): HasMany
    {
        return $this->hasMany(Incidencias::class, 'id_local');
    }
}