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
        $permission = 'maintenances.upload_evidence';

        // Insertar el permiso solo si no existe
        if (!DB::table('permissions')->where('name', $permission)->exists()) {
            DB::table('permissions')->insert([
                'name'       => $permission,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permissionId = DB::table('permissions')
            ->where('name', $permission)
            ->value('id');

        // Asignar a Administrador (id=1)
        $exists = DB::table('role_permission')
            ->where('role_id', 1)
            ->where('permission_id', $permissionId)
            ->exists();

        if (!$exists) {
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
        $permission = 'maintenances.upload_evidence';

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
    }
};
