<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todos los roles
        $administrador = Role::where('id', 1)->first(); // Administrador
        $logistica = Role::where('id', 2)->first(); // Logística
        $operaciones = Role::where('id', 3)->first(); // Operaciones
        $documentacion = Role::where('id', 4)->first(); // Documentación
        $mantenimiento = Role::where('id', 5)->first(); // Mantenimiento
        $tesoreria = Role::where('id', 6)->first(); // Tesorería
        $direccion = Role::where('id', 7)->first(); // Dirección
        $chofer = Role::where('id', 8)->first(); // Chofer

        // Obtener todos los permisos para Administrador
        $todosLosPermisos = Permission::pluck('name')->toArray();

        // Administrador (role_id=1) - Todos los permisos
        $administrador->permissions()->sync(
            Permission::pluck('id')->toArray()
        );

        // Logística (role_id=2)
        $logistica->permissions()->sync(
            Permission::whereIn('name', [
                'services.view',
                'services.create',
                'services.edit',
                'services.delete',
                'services.download',
                'clients.view',
                'clients.create',
                'clients.edit',
                'clients.delete',
                'places.view',
            ])->pluck('id')->toArray()
        );

        // Operaciones (role_id=3)
        $operaciones->permissions()->sync(
            Permission::whereIn('name', [
                'services.view',
                'services.assign',
                'services.cancel',
                'services.reassign',
                'services.request_diesel',
                'services.download',
                'operators.view',
                'units.view',
            ])->pluck('id')->toArray()
        );

        // Documentación (role_id=4)
        $documentacion->permissions()->sync(
            Permission::whereIn('name', [
                'services.view',
                'services.request_booth',
                'services.download',
                'costs.view',
                'costs.edit',
                'expenses.view',
                'expenses.edit',
                'places.view',
                'places.create',
                'places.edit',
                'places.delete',
                'booths.view',
                'booths.create',
                'booths.edit',
                'booths.delete',
                'clients.view',
                'operators.view',
                'units.view',
                'operator_payments.view',
                'operator_payments.create',
                'operator_payments.edit',
            ])->pluck('id')->toArray()
        );

        // Mantenimiento (role_id=5)
        $mantenimiento->permissions()->sync(
            Permission::whereIn('name', [
                'maintenances.view',
                'maintenances.create',
                'maintenances.edit',
                'maintenances.delete',
                'maintenances.change_state',
                'units.view',
                'units.create',
                'units.edit',
                'units.delete',
                'inventories.view',
                'inventories.create',
                'inventories.edit',
                'inventories.delete',
                'suppliers.view',
                'suppliers.create',
                'suppliers.edit',
                'suppliers.delete',
                'tires.view',
                'tires.create',
                'tires.edit',
                'tires.delete',
                'services.view',
                'services.request_diesel',
            ])->pluck('id')->toArray()
        );

        // Tesorería (role_id=6)
        $tesoreria->permissions()->sync(
            Permission::whereIn('name', [
                'treasury.view_services',
                'treasury.view_maintenances',
                'treasury.view_payments',
                'treasury.apply_payment',
                'treasury.upload_evidence',
                'treasury.init_expenses',
                'treasury.ext_expenses',
            ])->pluck('id')->toArray()
        );

        // Dirección (role_id=7)
        $direccion->permissions()->sync(
            Permission::whereIn('name', [
                'services.view',
                'services.download',
                'dashboard.view_services',
                'dashboard.view_maintenances',
            ])->pluck('id')->toArray()
        );

        // Chofer (role_id=8)
        $chofer->permissions()->sync(
            Permission::whereIn('name', [
                'services.view',
                'services.change_substate',
                'services.request_booth',
                'operator_payments.view',
            ])->pluck('id')->toArray()
        );

        $this->command->info('Permisos asignados a roles exitosamente.');
    }
}
