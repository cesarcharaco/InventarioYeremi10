<?php

namespace App\Providers;


use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate; 
use App\Models\User;     
use Illuminate\Support\Facades\DB; // Añadido al namespace           
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        
        // Gestión de Proveedores: Solo Admin y Encargado
        Gate::define('gestionar-proveedores', function (User $user) {
            return in_array($user->role, [User::ROLE_SUPERADMIN, User::ROLE_ENCARGADO]);
        });

        // Gestión de Compras
        Gate::define('registro-compra', function (User $user) {
            return in_array($user->role, [User::ROLE_SUPERADMIN, User::ROLE_ENCARGADO, User::ROLE_ALMACENISTA]);
        });

        // Ver Precios de Costo: Solo Admin y Encargado
        Gate::define('ver-costos', function (User $user) {
            return in_array($user->role, [User::ROLE_SUPERADMIN, User::ROLE_ENCARGADO]);
        });

        // Puede entrar a la vista de crear/editar y ver botones de acción
        Gate::define('gestionar-insumos', function (User $user) {
            return in_array($user->role, [User::ROLE_SUPERADMIN, User::ROLE_ENCARGADO, User::ROLE_ALMACENISTA]);
        });

        // Solo Admin/SuperAdmin pueden cambiar datos sensibles (Precios, Nombres, Seriales)
        Gate::define('editar-datos-maestros', function (User $user) {
            return in_array($user->role, [User::ROLE_SUPERADMIN, User::ROLE_ENCARGADO]);
        });

        // Anular Historial/Incidencias: Solo el SuperAdmin (Dueño)
        Gate::define('anular-historial', function (User $user) {
            return $user->role === User::ROLE_SUPERADMIN;
        });
        
        // Acceso a Logística: Todos menos el Vendedor
        Gate::define('ver-logistica', function (User $user) {
            return $user->role !== User::ROLE_VENDEDOR;
        });

        // registro de despachos de mercancia
        Gate::define('crear-despacho', function (User $user) {
            return in_array($user->role, [User::ROLE_SUPERADMIN, User::ROLE_ENCARGADO, User::ROLE_ALMACENISTA]);
        });

        // confirmación de recepcion de mercancia
        Gate::define('recibir-despacho', function (User $user) {
            return in_array($user->role, [User::ROLE_SUPERADMIN, User::ROLE_ENCARGADO, User::ROLE_ALMACENISTA]);
        });
        
        // editar de despachos de mercancia
        Gate::define('editar-despacho', function (User $user) {
            return in_array($user->role, [User::ROLE_SUPERADMIN, User::ROLE_ENCARGADO, User::ROLE_ALMACENISTA]);
        });

        // eliminar de despachos de mercancia
        Gate::define('eliminar-despacho', function (User $user) {
            return in_array($user->role, [User::ROLE_SUPERADMIN, User::ROLE_ENCARGADO, User::ROLE_ALMACENISTA]);
        });

        //Registro de incidencias todos
        Gate::define('registrar-incidencia', function (User $user) {
            return in_array($user->role, [User::ROLE_SUPERADMIN, User::ROLE_ENCARGADO, User::ROLE_ALMACENISTA, User::ROLE_VENDEDOR]);
        });

        //Ver historial total de todas las incidencias
        Gate::define('ver-historial-total', function (User $user) {
            return in_array($user->role, [User::ROLE_SUPERADMIN, User::ROLE_ENCARGADO]);
        });

        // Solo el SuperAdmin puede anular registros del historial
        Gate::define('anular-historial', function ($user) {
            return $user->role === User::ROLE_SUPERADMIN;
        });

        // Crear nuevas tiendas o depositos
        Gate::define('gestionar-locales', function ($user) {
            return $user->role === User::ROLE_SUPERADMIN;
        });

        // Gráficas de ganancias y rendimiento global
        Gate::define('ver-reportes', function ($user) {
            return $user->role === User::ROLE_SUPERADMIN;
        });

        // Configuraciones Maestras (Categorías, Marcas, Locales)
        // Generalmente solo el Admin debería tocar estas tablas
        Gate::define('crear-configuracion', function (User $user) {
            return in_array($user->role, [User::ROLE_SUPERADMIN, User::ROLE_ENCARGADO]);
        });
        Gate::define('editar-configuracion', function (User $user) {
            return in_array($user->role, [User::ROLE_SUPERADMIN, User::ROLE_ENCARGADO]);
        });
        Gate::define('eliminar-configuracion', function (User $user) {
            return in_array($user->role, [User::ROLE_SUPERADMIN, User::ROLE_ENCARGADO]);
        });
        
        // 1. GATE GLOBAL: Solo el dueño y el de almacén central
        Gate::define('gestionar-estado-global', function ($user) {
            // Solo permitimos a los que tienen poder nacional
            return in_array($user->role, [User::ROLE_SUPERADMIN, User::ROLE_ALMACENISTA]);
        });

        // 2. GATE LOCAL: SuperAdmin, Almacenista O el Encargado de SU tienda
        Gate::define('gestionar-estado-local', function ($user, $id_local_item) {
            
            // Los rangos altos pueden editar CUALQUIER local
            if (in_array($user->role, [User::ROLE_SUPERADMIN, User::ROLE_ALMACENISTA])) {
                return true;
            }

            // El encargado SOLO puede editar si el ID del local coincide en la tabla pivot
            if ($user->role === User::ROLE_ENCARGADO) {
                return DB::table('users_has_local')
                    ->where('id_user', $user->id)
                    ->where('id_local', (int)$id_local_item)
                    ->exists();
            }

            return false;
        });

    }
}
