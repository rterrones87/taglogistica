<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_operator_type_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_operator_type_id');
            $table->string('name', 100);
            $table->decimal('amount', 10, 2);
            $table->tinyInteger('zombie')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('service_operator_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_operator_type_rates');
    }
};
