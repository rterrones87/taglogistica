<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('purchase_orders')
            ->whereNull('payment_condition')
            ->update([
                'payment_condition' => 'Contado',
                'credit_days' => null,
            ]);
    }

    public function down(): void
    {
        // No se puede distinguir el Contado original del normalizado.
    }
};
