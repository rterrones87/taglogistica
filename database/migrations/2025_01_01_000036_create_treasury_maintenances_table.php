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
        Schema::create('treasury_maintenances', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('maintenance_id');
            $table->integer('user_id');
            $table->string('order_date', 10);
            $table->decimal('total', 10, 0);
            $table->longText('description');
            $table->integer('paid')->default(0);
            $table->string('payment_date', 10)->nullable();
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
        Schema::dropIfExists('treasury_maintenances');
    }
};
