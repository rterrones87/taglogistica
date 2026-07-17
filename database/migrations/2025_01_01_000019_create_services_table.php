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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->integer('client_id');
            $table->string('folio', 20);
            $table->integer('type_operation');
            $table->string('terminal', 100);
            $table->integer('type_unit')->nullable();
            $table->integer('unit_id')->default(0);
            $table->integer('IMO')->default(0);
            $table->string('dispatch_date');
            $table->string('delivery_date');
            $table->integer('operator_id')->default(0);
            $table->string('diesel')->nullable();
            $table->integer('state_id')->default(1);
            $table->integer('substate_id')->default(0);
            $table->integer('aux_unit_id')->nullable();
            $table->integer('aux_operator_id')->nullable();
            $table->integer('aux2_unit_id')->nullable();
            $table->integer('aux2_operator_id')->nullable();
            $table->integer('order_paid')->default(0);
            $table->string('commission', 11)->nullable();
            $table->integer('diesel_cost_id')->default(0);
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
        Schema::dropIfExists('services');
    }
};
