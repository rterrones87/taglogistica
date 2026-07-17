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
        // Insertar permiso roles.manage_permissions
        $permissionId = DB::table('permissions')->insertGetId([
            'name' => 'roles.manage_permissions',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Asignar permiso al rol Administrador (role_id = 1)
        DB::table('role_permission')->insert([
            'role_id' => 1,
            'permission_id' => $permissionId,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar relación rol-permiso
        DB::table('role_permission')
            ->whereIn('permission_id', function($query) {
                $query->select('id')
                      ->from('permissions')
                      ->where('name', 'roles.manage_permissions');
            })
            ->delete();

        // Eliminar permiso
        DB::table('permissions')
            ->where('name', 'roles.manage_permissions')
            ->delete();
    }
};
