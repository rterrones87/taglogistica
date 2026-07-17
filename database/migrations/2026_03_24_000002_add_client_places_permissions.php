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
        $permissions = [
            'client_places.view',
            'client_places.create',
            'client_places.edit',
            'client_places.delete',
        ];

        foreach ($permissions as $name) {
            DB::table('permissions')->insert([
                'name'       => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Asignar los 4 permisos al rol Administrador (id = 1)
        $permissionIds = DB::table('permissions')
            ->whereIn('name', $permissions)
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_permission')->insert([
                'role_id'       => 1,
                'permission_id' => $permissionId,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'client_places.view',
            'client_places.create',
            'client_places.edit',
            'client_places.delete',
        ];

        // Eliminar relaciones rol-permiso
        DB::table('role_permission')
            ->whereIn('permission_id', function ($query) use ($permissions) {
                $query->select('id')
                      ->from('permissions')
                      ->whereIn('name', $permissions);
            })
            ->delete();

        // Eliminar permisos
        DB::table('permissions')->whereIn('name', $permissions)->delete();
    }
};
