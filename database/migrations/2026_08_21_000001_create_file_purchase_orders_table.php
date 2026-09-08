<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->unsignedTinyInteger('type')->comment('1: Cotizacion, 2: Evidencia');
            $table->string('url');
            $table->timestamps();

            $table->index(['purchase_order_id', 'type']);
        });

        DB::table('purchase_orders')
            ->select(['id', 'quotation_path', 'evidence_path'])
            ->orderBy('id')
            ->chunkById(100, function ($orders) {
                $now = now();
                $files = [];

                foreach ($orders as $order) {
                    if ($order->quotation_path) {
                        $files[] = [
                            'purchase_order_id' => $order->id,
                            'type' => 1,
                            'url' => $order->quotation_path,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if ($order->evidence_path) {
                        $files[] = [
                            'purchase_order_id' => $order->id,
                            'type' => 2,
                            'url' => $order->evidence_path,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if ($files) {
                    DB::table('file_purchase_orders')->insert($files);
                }
            });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['quotation_path', 'evidence_path']);
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('quotation_path')->nullable();
            $table->string('evidence_path')->nullable();
        });

        DB::table('file_purchase_orders')
            ->orderBy('id')
            ->get()
            ->groupBy('purchase_order_id')
            ->each(function ($files, $purchaseOrderId) {
                DB::table('purchase_orders')
                    ->where('id', $purchaseOrderId)
                    ->update([
                        'quotation_path' => optional($files->firstWhere('type', 1))->url,
                        'evidence_path' => optional($files->firstWhere('type', 2))->url,
                    ]);
            });

        Schema::dropIfExists('file_purchase_orders');
    }
};
