<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Cliente extends Model
{
    use HasFactory;//, SoftDeletes;

    // Nombre de la tabla
    protected $table = 'clientes';

    /**
     * Atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'identificacion',
        'nombre',
        'telefono',
        'direccion',
        'email',
        'limite_credito',
        'id_local',
        'activo'
    ];

    /**
     * Atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'activo' => 'boolean',
        'limite_credito' => 'decimal:2',
    ];

    // --- RELACIONES ---

    /**
     * Obtiene el local donde fue registrado el cliente.
     */
    public function local()
    {
        // Según tu SQL, la tabla se llama 'local' y la FK en clientes será 'id_local'
        return $this->belongsTo(Local::class, 'id_local');
    }

    /**
     * Obtiene todos los créditos asociados al cliente.
     * (Esta relación la usaremos cuando creemos el modelo Credito)
     */
    public function creditos()
    {
        return $this->hasMany(Credito::class, 'id_cliente');
    }

    // --- SCOPES (Para búsquedas rápidas) ---

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeByIdentificacion($query, $id)
    {
        return $query->where('identificacion', 'LIKE', "%$id%");
    }
}
