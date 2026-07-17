<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_operator_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50);
            $table->tinyInteger('type_operation');
            $table->tinyInteger('is_main')->default(0);
            $table->tinyInteger('zombie')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        DB::table('service_operator_types')->insert([
            ['id' => 1, 'name' => 'Flete',                'code' => 'MAIN',       'type_operation' => 1, 'is_main' => 1, 'zombie' => 0],
            ['id' => 2, 'name' => 'Entrega de Vacío',     'code' => 'AUX_PATIO',  'type_operation' => 1, 'is_main' => 0, 'zombie' => 0],
            ['id' => 3, 'name' => 'Flete',                'code' => 'MAIN',       'type_operation' => 2, 'is_main' => 1, 'zombie' => 0],
            ['id' => 4, 'name' => 'Recolección de Vacío', 'code' => 'AUX_INICIO', 'type_operation' => 2, 'is_main' => 0, 'zombie' => 0],
            ['id' => 5, 'name' => 'Ingreso de Lleno',     'code' => 'AUX_FIN',    'type_operation' => 2, 'is_main' => 0, 'zombie' => 0],
            ['id' => 6, 'name' => 'Flete',                'code' => 'MAIN',       'type_operation' => 3, 'is_main' => 1, 'zombie' => 0],
            ['id' => 7, 'name' => 'Entrega de Vacío',     'code' => 'AUX_PATIO',  'type_operation' => 3, 'is_main' => 0, 'zombie' => 0],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('service_operator_types');
    }
};
