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
        Schema::create('substate_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('operator_id');
            $table->unsignedBigInteger('substate_id');
            $table->integer('state_id')->nullable();
            $table->timestamps();
            
            // Índices para mejorar performance de consultas
            $table->index('service_id');
            $table->index('operator_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('substate_history');
    }
};
