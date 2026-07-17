<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // Insertar datos pre-cargados
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'Administrador', 'created_at' => '2025-04-03 23:01:00', 'updated_at' => '2025-04-30 03:38:11'],
            ['id' => 2, 'name' => 'Logística', 'created_at' => '2025-04-03 23:01:00', 'updated_at' => '2025-04-30 03:39:48'],
            ['id' => 3, 'name' => 'Operaciones', 'created_at' => '2025-04-30 03:39:04', 'updated_at' => '2025-04-30 03:39:43'],
            ['id' => 4, 'name' => 'Documentación', 'created_at' => '2025-04-30 03:39:04', 'updated_at' => '2025-04-30 03:39:04'],
            ['id' => 5, 'name' => 'Mantenimiento', 'created_at' => '2025-04-30 03:39:24', 'updated_at' => '2025-04-30 03:39:24'],
            ['id' => 6, 'name' => 'Tesorería', 'created_at' => '2025-04-30 03:39:24', 'updated_at' => '2025-04-30 03:39:24'],
            ['id' => 7, 'name' => 'Dirección', 'created_at' => '2025-04-30 03:39:31', 'updated_at' => '2025-04-30 03:39:31'],
            ['id' => 8, 'name' => 'Chofer', 'created_at' => '2025-07-29 02:23:17', 'updated_at' => '2025-07-29 02:23:17'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
