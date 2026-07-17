<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('client_places', function (Blueprint $table) {
            $table->integer('type_unit_id')->nullable()->after('place_id');

            // Reemplazar el unique anterior por uno que incluye type_unit_id
            $table->dropUnique(['client_id', 'place_id']);
            $table->unique(['client_id', 'place_id', 'type_unit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_places', function (Blueprint $table) {
            $table->dropUnique(['client_id', 'place_id', 'type_unit_id']);
            $table->dropColumn('type_unit_id');
            $table->unique(['client_id', 'place_id']);
        });
    }
};
