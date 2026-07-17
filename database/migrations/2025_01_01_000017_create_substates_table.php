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
        Schema::create('substates', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('type_operation');
            $table->string('name', 50);
            $table->integer('zombie')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // Insertar datos pre-cargados
        DB::table('substates')->insert([
            ['id' => 1, 'type_operation' => 1, 'name' => 'Recoge carga en puerto', 'zombie' => 0, 'created_at' => '2025-07-19 16:29:43', 'updated_at' => '2025-07-29 01:38:27'],
            ['id' => 2, 'type_operation' => 1, 'name' => 'Inicia flete', 'zombie' => 0, 'created_at' => '2025-07-19 16:29:43', 'updated_at' => '2025-07-29 01:38:34'],
            ['id' => 3, 'type_operation' => 1, 'name' => 'Llegada a destino', 'zombie' => 0, 'created_at' => '2025-07-19 16:31:07', 'updated_at' => '2025-07-29 01:38:48'],
            ['id' => 4, 'type_operation' => 1, 'name' => 'Inicio descarga', 'zombie' => 0, 'created_at' => '2025-07-19 16:31:07', 'updated_at' => '2025-07-19 16:31:07'],
            ['id' => 5, 'type_operation' => 1, 'name' => 'Termino descarga', 'zombie' => 0, 'created_at' => '2025-07-19 16:32:47', 'updated_at' => '2025-07-29 01:49:14'],
            ['id' => 6, 'type_operation' => 1, 'name' => 'Inicio salida', 'zombie' => 0, 'created_at' => '2025-07-19 16:32:47', 'updated_at' => '2025-07-29 01:40:23'],
            ['id' => 7, 'type_operation' => 1, 'name' => 'Llegada a patio TAG', 'zombie' => 0, 'created_at' => '2025-07-19 16:33:39', 'updated_at' => '2025-07-19 16:36:33'],
            ['id' => 8, 'type_operation' => 1, 'name' => 'Entrega de vacio', 'zombie' => 0, 'created_at' => '2025-07-19 16:33:39', 'updated_at' => '2025-07-19 16:33:39'],
            ['id' => 9, 'type_operation' => 2, 'name' => 'Recolección de vacio', 'zombie' => 0, 'created_at' => '2025-07-19 16:38:20', 'updated_at' => '2025-07-19 16:38:20'],
            ['id' => 10, 'type_operation' => 2, 'name' => 'Recolección de carga', 'zombie' => 0, 'created_at' => '2025-07-19 16:38:20', 'updated_at' => '2025-07-29 01:43:52'],
            ['id' => 11, 'type_operation' => 2, 'name' => 'Salida a cargar', 'zombie' => 0, 'created_at' => '2025-07-19 16:39:18', 'updated_at' => '2025-07-29 01:43:55'],
            ['id' => 12, 'type_operation' => 2, 'name' => 'Llegada a cargar', 'zombie' => 0, 'created_at' => '2025-07-19 16:39:18', 'updated_at' => '2025-07-29 01:44:09'],
            ['id' => 13, 'type_operation' => 2, 'name' => 'Inicia a cargar', 'zombie' => 0, 'created_at' => '2025-07-19 16:40:18', 'updated_at' => '2025-07-29 01:55:53'],
            ['id' => 14, 'type_operation' => 2, 'name' => 'Finalización de carga', 'zombie' => 0, 'created_at' => '2025-07-19 16:40:18', 'updated_at' => '2025-07-29 01:44:43'],
            ['id' => 15, 'type_operation' => 2, 'name' => 'Salida a entrega', 'zombie' => 0, 'created_at' => '2025-07-19 16:42:04', 'updated_at' => '2025-07-29 01:55:32'],
            ['id' => 16, 'type_operation' => 2, 'name' => 'Contenedor en patio', 'zombie' => 0, 'created_at' => '2025-07-19 16:42:04', 'updated_at' => '2025-07-29 01:45:33'],
            ['id' => 17, 'type_operation' => 2, 'name' => 'Salida a entregar carga', 'zombie' => 0, 'created_at' => '2025-07-19 16:43:56', 'updated_at' => '2025-07-29 01:46:21'],
            ['id' => 18, 'type_operation' => 2, 'name' => 'Entrega de carga', 'zombie' => 0, 'created_at' => '2025-07-29 01:46:46', 'updated_at' => '2025-07-29 01:46:46'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('substates');
    }
};
