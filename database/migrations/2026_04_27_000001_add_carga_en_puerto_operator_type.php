<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Insertar los nuevos tipos de operador
        DB::table('service_operator_types')->insert([
            [
                'id'             => 8,
                'name'           => 'Carga en Puerto',
                'code'           => 'AUX_PUERTO',
                'type_operation' => 1,
                'is_main'        => 0,
                'zombie'         => 0,
            ],
            [
                'id'             => 9,
                'name'           => 'Carga en Puerto',
                'code'           => 'AUX_PUERTO',
                'type_operation' => 3,
                'is_main'        => 0,
                'zombie'         => 0,
            ],
        ]);

        // 2. Asignar substate 1 (Cargar en Puerto) a los nuevos tipos
        DB::table('service_operator_type_substates')->insert([
            ['service_operator_type_id' => 8, 'substate_id' => 1],
            ['service_operator_type_id' => 9, 'substate_id' => 1],
        ]);

        // 3. Remover substate 1 de los tipos MAIN (Flete Importación=1, Flete Carga Suelta=6)
        //    para que solo "Carga en Puerto" sea el activo en ese substate
        DB::table('service_operator_type_substates')
            ->whereIn('service_operator_type_id', [1, 6])
            ->where('substate_id', 1)
            ->delete();
    }

    public function down(): void
    {
        // Revertir: restaurar substate 1 en los tipos MAIN
        DB::table('service_operator_type_substates')->insertOrIgnore([
            ['service_operator_type_id' => 1, 'substate_id' => 1],
            ['service_operator_type_id' => 6, 'substate_id' => 1],
        ]);

        // Eliminar los substates de los nuevos tipos
        DB::table('service_operator_type_substates')
            ->whereIn('service_operator_type_id', [8, 9])
            ->delete();

        // Eliminar los nuevos tipos
        DB::table('service_operator_types')
            ->whereIn('id', [8, 9])
            ->delete();
    }
};
