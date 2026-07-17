<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_operator_type_substates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_operator_type_id');
            $table->integer('substate_id');
            $table->unique(['service_operator_type_id', 'substate_id']);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        $rows = [];

        // Tipo 1 (Flete Importación) y Tipo 6 (Flete Carga Suelta): substates 0,1,2,3,4,5,6,8
        foreach ([0, 1, 2, 3, 4, 5, 6, 8] as $s) {
            $rows[] = ['service_operator_type_id' => 1, 'substate_id' => $s];
            $rows[] = ['service_operator_type_id' => 6, 'substate_id' => $s];
        }

        // Tipo 2 (Entrega de Vacío Importación) y Tipo 7 (Entrega de Vacío Carga Suelta): substate 7
        $rows[] = ['service_operator_type_id' => 2, 'substate_id' => 7];
        $rows[] = ['service_operator_type_id' => 7, 'substate_id' => 7];

        // Tipo 3 (Flete Exportación): substates 9,10,11,12,13,14,15,16,18
        foreach ([9, 10, 11, 12, 13, 14, 15, 16, 18] as $s) {
            $rows[] = ['service_operator_type_id' => 3, 'substate_id' => $s];
        }

        // Tipo 4 (Recolección de Vacío Exportación): substate 0
        $rows[] = ['service_operator_type_id' => 4, 'substate_id' => 0];

        // Tipo 5 (Ingreso de Lleno Exportación): substate 17
        $rows[] = ['service_operator_type_id' => 5, 'substate_id' => 17];

        DB::table('service_operator_type_substates')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('service_operator_type_substates');
    }
};
