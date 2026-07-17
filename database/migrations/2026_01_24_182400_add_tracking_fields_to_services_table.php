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
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedBigInteger('initial_expenses_filled_by')->nullable()->after('diesel');
            $table->unsignedBigInteger('initial_diesel_filled_by')->nullable()->after('initial_expenses_filled_by');
            
            // Agregar foreign keys
            $table->foreign('initial_expenses_filled_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('initial_diesel_filled_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['initial_expenses_filled_by']);
            $table->dropForeign(['initial_diesel_filled_by']);
            $table->dropColumn(['initial_expenses_filled_by', 'initial_diesel_filled_by']);
        });
    }
};
