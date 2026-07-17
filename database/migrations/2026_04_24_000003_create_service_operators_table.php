<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_operators', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('operator_id');
            $table->unsignedBigInteger('unit_id')->default(0);
            $table->unsignedBigInteger('service_operator_type_id');
            $table->decimal('amount_bonus', 10, 2)->default(0);
            $table->tinyInteger('zombie')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['service_id', 'service_operator_type_id']);
            $table->index('service_id');
            $table->index('operator_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_operators');
    }
};
