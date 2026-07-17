<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\User;
use App\Models\Service;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        /**
         * Gate: Verifica si el usuario puede ver todos los servicios
         * Los choferes solo ven sus propios servicios asignados
         */
        Gate::define('view-all-services', function (User $user) {
            return $user->role_id !== 8; // No es Chofer
        });

        /**
         * Gate: Verifica si el usuario puede ver los pagos de un operador específico
         * Los choferes solo pueden ver sus propios pagos
         */
        Gate::define('view-own-payments', function (User $user, $operatorId) {
            // Si el usuario es chofer, solo puede ver sus propios pagos
            if ($user->role_id === 8) {
                return $user->id == $operatorId;
            }
            // Otros roles pueden ver todos los pagos
            return true;
        });

        /**
         * Gate: Verifica si un servicio puede ser editado según su estado
         * - Servicios en estado "En Espera" (state_id = 1) siempre se pueden editar
         * - Exportaciones (type_operation = 2):
         *   - En estado "Programado" (state_id = 2): puede editar número de contenedor
         *   - En estado "En Ruta" (state_id = 3) con substate_id <= 9: puede editar número de contenedor
         */
        Gate::define('edit-service-in-state', function (User $user, Service $service) {
            // Permitir edición completa si está en estado "En Espera"
            if ($service->state_id == 1) {
                return true;
            }
            
            // Para exportaciones, permitir edición de número de contenedor
            if ($service->type_operation == 2) {
                // En estado "Programado", puede editar
                if ($service->state_id == 2) {
                    return true;
                }
                
                // En estado "En Ruta", solo hasta el subestado 9
                if ($service->state_id == 3 && $service->substate_id > 0 && $service->substate_id <= 9) {
                    return true;
                }
            }
            
            return false;
        });

        /**
         * Gate: Verifica si un servicio puede ser cancelado
         * Solo se pueden cancelar servicios que no estén finalizados o cancelados
         */
        Gate::define('cancel-service', function (User $user, Service $service) {
            return $service->state_id < 5; // Menor a Finalizado
        });
    }
}
