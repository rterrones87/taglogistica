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
        $updates = [
            9  => 'Recolección de Vacío',
            10 => 'Vacío Cargado',
            11 => 'Inicia Flete',
            12 => 'Llegada a Cliente',
            13 => 'Inicia Carga',
            14 => 'Finaliza Carga',
            15 => 'Salida de Cliente',
            16 => 'Llegada a Patio TAG',
            17 => 'Inicia Ingreso de Carga',
            18 => 'Ingreso de Carga Concluido',
        ];

        foreach ($updates as $id => $name) {
            DB::table('substates')->where('id', $id)->update(['name' => $name]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $originals = [
            9  => 'Recolección de vacio',
            10 => 'Recolección de carga',
            11 => 'Salida a cargar',
            12 => 'Llegada a cargar',
            13 => 'Inicia a cargar',
            14 => 'Finalización de carga',
            15 => 'Salida a entrega',
            16 => 'Contenedor en patio',
            17 => 'Salida a entregar carga',
            18 => 'Entrega de carga',
        ];

        foreach ($originals as $id => $name) {
            DB::table('substates')->where('id', $id)->update(['name' => $name]);
        }
    }
};
