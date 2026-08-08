<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('failure_reports', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 20)->unique();
            $table->foreignId('unit_id')->constrained('units');
            $table->foreignId('operator_id')->constrained('users');
            $table->unsignedInteger('mileage');
            $table->date('reported_at');
            $table->text('description');
            $table->enum('status', ['Abierto', 'En Proceso', 'Finalizado'])->default('Abierto');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('started_by')->nullable()->constrained('users');
            $table->timestamp('started_at')->nullable();
            $table->foreignId('finished_by')->nullable()->constrained('users');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'reported_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failure_reports');
    }
};
