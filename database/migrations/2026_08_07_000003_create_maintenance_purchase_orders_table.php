<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 20)->unique();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->text('description');
            $table->decimal('cost', 12, 2);
            $table->enum('payment_condition', ['Contado', 'Credito'])->nullable();
            $table->unsignedSmallInteger('credit_days')->nullable();
            $table->string('quotation_path')->nullable();
            $table->string('evidence_path')->nullable();
            $table->enum('status', ['Abierta', 'Cerrada'])->default('Abierta');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['work_order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
