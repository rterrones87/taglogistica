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
        // Todos los registros existentes se marcarán como 'outbound' (ida)
        // Esto mantiene compatibilidad con viajes antiguos
        DB::table('destinations')
            ->whereNull('direction')
            ->orWhere('direction', '')
            ->update(['direction' => 'outbound']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No es necesario revertir
    }
};
