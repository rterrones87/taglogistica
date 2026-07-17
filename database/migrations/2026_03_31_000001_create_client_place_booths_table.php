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
        Schema::create('client_place_booths', function (Blueprint $table) {
            $table->id();
            $table->integer('client_place_id');
            $table->integer('booth_id');
            $table->enum('direction', ['outbound', 'return'])->default('outbound');
            $table->timestamps();

            $table->unique(['client_place_id', 'booth_id', 'direction']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_place_booths');
    }
};
