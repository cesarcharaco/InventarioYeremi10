<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Hash; // Opcional aquí, pero útil

class User extends Authenticatable
{
    use Notifiable;

    // --- CONSTANTES DE JERARQUÍA ---
    const ROLE_SUPERADMIN = 'admin';       // El dueño / Jefe técnico
    const ROLE_ALMACENISTA = 'almacenista';// El que mueve mercancía global
    const ROLE_ENCARGADO = 'encargado';     // El jefe de UNA tienda específica
    const ROLE_VENDEDOR = 'vendedor';       // Solo ventas

    protected $fillable = [
        'name', 'cedula', 'telefono', 'email', 'password', 'role', 'activo', 'foto'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'activo' => 'boolean', // Para que Laravel lo trate como true/false automáticamente
    ];

    // --- RELACIONES ---
    public function local(): BelongsToMany
    {
        return $this->belongsToMany(Local::class, 'users_has_local', 'id_user', 'id_local')
                    ->withPivot('status');
    }

    // --- MÉTODOS DE APOYO (Helpers) ---
    public function hasRole($role): bool
    {
        return $this->role === $role;
    }

    public function localActual()
    {
        return $this->local()->wherePivot('status', 'activo')->first();
    }

    public function esAdmin(): bool
    {
        //return $this->role === self::ROLE_SUPERADMIN;
        return $this->role === 'admin';
    }

    public function abonosRecibidos(): HasMany
    {
        return $this->hasMany(AbonoCredito::class, 'id_user');
    }
}