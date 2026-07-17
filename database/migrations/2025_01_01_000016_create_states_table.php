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
        Schema::create('states', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name', 50);
            $table->integer('zombie')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // Insertar datos pre-cargados
        DB::table('states')->insert([
            ['id' => 1, 'name' => 'Solicitado', 'zombie' => 0, 'created_at' => '2025-05-22 02:44:52', 'updated_at' => '2025-06-13 16:41:51'],
            ['id' => 2, 'name' => 'Programado', 'zombie' => 0, 'created_at' => '2025-05-22 02:44:52', 'updated_at' => '2025-05-22 02:44:52'],
            ['id' => 3, 'name' => 'En Ruta', 'zombie' => 0, 'created_at' => '2025-05-22 02:45:21', 'updated_at' => '2025-05-22 02:45:21'],
            ['id' => 4, 'name' => 'Carga Entregada', 'zombie' => 0, 'created_at' => '2025-05-22 02:45:21', 'updated_at' => '2025-05-22 02:45:21'],
            ['id' => 5, 'name' => 'Finalizado', 'zombie' => 0, 'created_at' => '2025-05-22 02:51:37', 'updated_at' => '2025-05-22 02:51:37'],
            ['id' => 6, 'name' => 'Cancelado', 'zombie' => 0, 'created_at' => '2025-05-22 02:52:09', 'updated_at' => '2025-05-22 02:52:09'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('states');
    }
};
