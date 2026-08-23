<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treasury_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->unique()->constrained('purchase_orders')->cascadeOnDelete();
            $table->enum('status', ['Pendiente', 'Pagado'])->default('Pendiente');
            $table->foreignId('paid_by')->nullable()->constrained('users');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        $now = now();

        DB::table('purchase_orders')
            ->where('status', 'Aprobada')
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($now) {
                DB::table('treasury_purchase_orders')->insert(
                    $orders->map(fn ($order) => [
                        'purchase_order_id' => $order->id,
                        'status' => 'Pendiente',
                        'created_at' => $order->updated_at ?? $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('treasury_purchase_orders');
    }
};
