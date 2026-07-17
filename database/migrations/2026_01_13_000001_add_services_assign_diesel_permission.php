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
        // Insertar permiso services.assign_diesel
        DB::table('permissions')->insert([
            'name' => 'services.assign_diesel',
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
                      ->where('name', 'services.assign_diesel');
            })
            ->delete();

        // Eliminar permiso
        DB::table('permissions')
            ->where('name', 'services.assign_diesel')
            ->delete();
    }
};
