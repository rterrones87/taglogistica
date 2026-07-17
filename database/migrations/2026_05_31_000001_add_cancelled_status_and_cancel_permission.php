<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar estado Cancelado
        if (!DB::table('maintenance_status')->where('id', 5)->exists()) {
            DB::table('maintenance_status')->insert([
                'id' => 5,
                'name' => 'Cancelado',
                'zombie' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Agregar permiso maintenances.cancel
        if (!DB::table('permissions')->where('name', 'maintenances.cancel')->exists()) {
            DB::table('permissions')->insert([
                'name' => 'maintenances.cancel',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permissionId = DB::table('permissions')
            ->where('name', 'maintenances.cancel')
            ->value('id');

        // Asignar a Administrador (1) y Mantenimiento (6)
        foreach ([1, 6] as $roleId) {
            $exists = DB::table('role_permission')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->exists();

            if (!$exists) {
                DB::table('role_permission')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = 'maintenances.cancel';

        // Eliminar relaciones rol-permiso
        DB::table('role_permission')
            ->whereIn('permission_id', function ($query) use ($permission) {
                $query->select('id')
                      ->from('permissions')
                      ->where('name', $permission);
            })
            ->delete();

        // Eliminar el permiso
        DB::table('permissions')->where('name', $permission)->delete();

        // Eliminar estado Cancelado
        DB::table('maintenance_status')->where('id', 5)->delete();
    }
};
