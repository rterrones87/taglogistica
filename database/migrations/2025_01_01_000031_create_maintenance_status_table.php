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
        Schema::create('maintenance_status', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name', 100);
            $table->integer('zombie')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // Insertar datos pre-cargados
        DB::table('maintenance_status')->insert([
            ['id' => 1, 'name' => 'Solicitado', 'zombie' => 0, 'created_at' => '2025-09-17 03:46:51', 'updated_at' => '2025-09-17 03:46:51'],
            ['id' => 2, 'name' => 'Pendiente', 'zombie' => 0, 'created_at' => '2025-09-17 03:46:51', 'updated_at' => '2025-09-17 03:46:51'],
            ['id' => 3, 'name' => 'En Proceso', 'zombie' => 0, 'created_at' => '2025-09-17 03:47:26', 'updated_at' => '2025-09-17 03:47:26'],
            ['id' => 4, 'name' => 'Finalizado', 'zombie' => 0, 'created_at' => '2025-09-17 03:47:26', 'updated_at' => '2025-09-17 03:47:26'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_status');
    }
};
