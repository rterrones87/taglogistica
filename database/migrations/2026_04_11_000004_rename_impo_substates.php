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
            1 => 'Cargar en Puerto',
            3 => 'Llegada a Cliente',
            6 => 'Salida de Cliente',
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
            1 => 'Recoge carga en puerto',
            3 => 'Llegada a destino',
            6 => 'Inicio salida',
        ];

        foreach ($originals as $id => $name) {
            DB::table('substates')->where('id', $id)->update(['name' => $name]);
        }
    }
};
