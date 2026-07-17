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
        Schema::create('parts_supplier_requests', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('maintenance_id');
            $table->integer('supplier_id');
            $table->integer('request_type');
            $table->longText('description');
            $table->integer('quantity');
            $table->string('cost', 100);
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
        Schema::dropIfExists('parts_supplier_requests');
    }
};
