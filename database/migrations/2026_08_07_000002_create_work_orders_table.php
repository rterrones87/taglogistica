<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 20)->unique();
            $table->string('unit_category', 40);
            $table->enum('maintenance_type', ['Preventivo', 'Correctivo'])->nullable();
            $table->foreignId('unit_id')->constrained('units');
            $table->unsignedInteger('initial_mileage');
            $table->date('opened_at');
            $table->foreignId('operator_id')->constrained('users');
            $table->foreignId('mechanic_id')->constrained('users');
            $table->text('failure_description');
            $table->enum('work_type', ['Interno', 'Externo']);
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->enum('status', ['Abierto', 'En Proceso', 'Cerrado'])->default('Abierto');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('started_by')->nullable()->constrained('users');
            $table->timestamp('started_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'opened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
