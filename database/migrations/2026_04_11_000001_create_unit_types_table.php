<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('unit_types', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name', 100);
            $table->integer('zombie')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        DB::table('unit_types')->insert([
            ['id' => 1,  'name' => 'FULL',                   'zombie' => 0],
            ['id' => 2,  'name' => 'SENCILLO',               'zombie' => 0],
            ['id' => 3,  'name' => 'LOWBOY',                 'zombie' => 0],
            ['id' => 4,  'name' => 'CAMA BAJA',              'zombie' => 0],
            ['id' => 5,  'name' => 'CONTENEDOR RF FULL',     'zombie' => 0],
            ['id' => 6,  'name' => 'CONTENEDOR RF SENCILLO', 'zombie' => 0],
            ['id' => 7,  'name' => 'CAJA RF FULL',           'zombie' => 0],
            ['id' => 8,  'name' => 'CAJA RF SENCILLO',       'zombie' => 0],
            ['id' => 9,  'name' => 'PLANA FULL',             'zombie' => 0],
            ['id' => 10, 'name' => 'PLANA SENCILLO',         'zombie' => 0],
            ['id' => 11, 'name' => 'RECOLECCIÓN FULL',       'zombie' => 0],
            ['id' => 12, 'name' => 'RECOLECCIÓN SENCILLO',   'zombie' => 0],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_types');
    }
};
