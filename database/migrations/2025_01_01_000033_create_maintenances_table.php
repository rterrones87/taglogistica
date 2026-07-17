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
        Schema::create('maintenances', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('folio', 20);
            $table->integer('type_maintenance_id')->default(0);
            $table->string('description');
            $table->string('kms', 10);
            $table->string('init_date');
            $table->integer('unit_id')->default(0);
            $table->integer('maintenance_status_id')->default(1);
            $table->integer('zombie')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
