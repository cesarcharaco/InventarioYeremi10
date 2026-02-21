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

        // Editar Precios de Costo: Solo Admin y Encargado
        Gate::define('editar-costos', function (User $user) {
            return in_array($user->role, [User::ROLE_SUPERADMIN]);
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

        // Define quién puede elegir CUALQUIER local como origen (Poder Global)
        Gate::define('seleccionar-cualquier-origen', function (User $user) {
            return in_array($user->role, [User::ROLE_SUPERADMIN, User::ROLE_ALMACENISTA]);
        });

        //Registro de incidencias todos
        Gate::define('registrar-incidencia', function (User $user) {
            return in_array($user->role, [User::ROLE_SUPERADMIN, User::ROLE_ENCARGADO, User::ROLE_ALMACENISTA, User::ROLE_VENDEDOR]);
        });

        //Ver historial total de todas las incidencias
        Gate::define('ver-historial-total', function (User $user) {
            return in_array($user->role, [User::ROLE_SUPERADMIN, User::ROLE_ENCARGADO]);
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

        // gestion del modulo de modelos de venta
        Gate::define('gestionar-modelos-venta', function ($user) {
            return $user->role === User::ROLE_SUPERADMIN;
        });

        // Solo el Dueño/SuperAdmin puede crear, editar o eliminar cuentas de acceso
        Gate::define('gestionar-usuarios', function ($user) {
            return $user->role === User::ROLE_SUPERADMIN;
        });
        
        // 1. Gestión base de clientes (Ver lista, Crear y Editar datos básicos)
        // Permitido para: SuperAdmin, Encargado y Vendedor
        Gate::define('gestionar-clientes', function (User $user) {
            return in_array($user->role, [
                User::ROLE_SUPERADMIN, 
                User::ROLE_ENCARGADO, 
                User::ROLE_VENDEDOR
            ]);
        });

        // 2. Gestión avanzada de créditos (Aumentar límites, Revalorizar deuda)
        // Permitido para: SuperAdmin y Encargado solamente
        Gate::define('gestionar-creditos-avanzado', function (User $user) {
            return in_array($user->role, [
                User::ROLE_SUPERADMIN, 
                User::ROLE_ENCARGADO
            ]);
        });

        // 3. Eliminar clientes (Acción crítica)
        // Permitido para: Solo SuperAdmin
        Gate::define('eliminar-clientes', function (User $user) {
            return $user->role === User::ROLE_SUPERADMIN;
        });

        // 1. Quién puede abrir y operar una caja (Día a día)
        Gate::define('operar-caja', function (User $user) {
            // Forzamos minúsculas para comparar
            $role = strtolower($user->role);
            return in_array($role, ['vendedor', 'encargado', 'admin']);
        });

        // 2. Quién puede ver reportes de cajas de otros (Auditoría)
        // El vendedor solo ve su propia caja, el admin ve todas.
        Gate::define('auditar-cajas', function (User $user) {
            return in_array($user->role, [
                User::ROLE_SUPERADMIN, 
                User::ROLE_ENCARGADO
            ]);
        });

        // 3. Quién puede reabrir una caja cerrada (Acción crítica por error humano)
        Gate::define('reabrir-caja', function (User $user) {
            return $user->role === User::ROLE_SUPERADMIN;
        });

        Gate::define('ver-historial-ventas', function (User $user) {
            $role = strtolower($user->role);
            return in_array($role, ['vendedor', 'encargado', 'admin']);
        });

        // Registrar pagos de deudas (abonos a crédito)
        Gate::define('gestionar-abonos', function (User $user) {
            $role = strtolower($user->role);
            return in_array($role, ['vendedor', 'encargado', 'admin']);
        });

        Gate::define('ver-ganancia-detalle', function (User $user) {
            return in_array($user->role, [User::ROLE_SUPERADMIN, User::ROLE_ENCARGADO]);
        });

        Gate::define('ver-autorizaciones', function ($user) {
            // Ajusta esto según tu lógica (ej: si es rol 'admin' o un ID específico)
            return $user->role === 'admin'; 
        });

    // --- MÓDULO DE CRÉDITOS ---

    // 1. Quién puede ver la lista de deudores y entrar al detalle
    Gate::define('ver-creditos', function (User $user) {
        $role = strtolower($user->role);
        return in_array($role, ['admin', 'encargado', 'vendedor']);
    });

    // 2. Quién puede registrar un abono (cobrar)
    Gate::define('registrar-abono', function (User $user) {
        $role = strtolower($user->role);
        // Permitimos a los tres roles operativos
        return in_array($role, ['admin', 'encargado', 'vendedor']);
    });

    // 3. Revalorizar deuda (Ajustar por inflación/tasa de cambio)
    // Acción sensible: Solo Admin y Encargado
    Gate::define('revalorizar-credito', function (User $user) {
        $role = strtolower($user->role);
        return in_array($role, ['admin', 'encargado']);
    });

    // 4. Eliminar o anular un abono mal cargado
    // Acción crítica: Solo Admin (Dueño)
    Gate::define('anular-abono', function (User $user) {
        return strtolower($user->role) === 'admin';
    });
    }
}
