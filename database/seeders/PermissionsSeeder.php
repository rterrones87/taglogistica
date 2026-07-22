<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Servicios/Viajes
            'services.view',
            'services.create',
            'services.edit',
            'services.delete',
            'services.assign',
            'services.cancel',
            'services.reassign',
            'services.request_diesel',
            'services.request_booth',
            'services.change_substate',
            'services.download',
            
            // Clientes
            'clients.view',
            'clients.create',
            'clients.edit',
            'clients.delete',
            
            // Usuarios
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.change_password',
            
            // Operadores
            'operators.view',
            'operators.create',
            'operators.edit',
            'operators.delete',
            'operators.view_payments',
            
            // Unidades
            'units.view',
            'units.create',
            'units.edit',
            'units.delete',
            
            // Mantenimientos
            'maintenances.view',
            'maintenances.create',
            'maintenances.edit',
            'maintenances.delete',
            'maintenances.change_state',
            'maintenances.upload_evidence',
            
            // Inventarios
            'inventories.view',
            'inventories.create',
            'inventories.edit',
            'inventories.delete',
            
            // Proveedores
            'suppliers.view',
            'suppliers.create',
            'suppliers.edit',
            'suppliers.delete',
            
            // Lugares
            'places.view',
            'places.create',
            'places.edit',
            'places.delete',
            
            // Casetas
            'booths.view',
            'booths.create',
            'booths.edit',
            'booths.delete',
            
            // Costos
            'costs.view',
            'costs.edit',
            
            // Gastos
            'expenses.view',
            'expenses.edit',
            
            // Tesorería
            'treasury.view_services',
            'treasury.view_maintenances',
            'treasury.view_payments',
            'treasury.apply_payment',
            'treasury.upload_evidence',
            'treasury.init_expenses',
            'treasury.ext_expenses',
            
            // Aprobaciones
            'approvals.view',
            'approvals.approve',
            'approvals.reject',
            
            // Dashboard
            'dashboard.view_services',
            'dashboard.view_maintenances',
            
            // Llantas
            'tires.view',
            'tires.create',
            'tires.edit',
            'tires.delete',
            
            // Pagos Operadores
            'operator_payments.view',
            'operator_payments.create',
            'operator_payments.edit',
            
            // Costos de Diesel
            'diesel_costs.view',
            'diesel_costs.create',
            'diesel_costs.edit',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        
        $this->command->info('Permisos creados exitosamente.');
    }
}
