<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diesel', function (Blueprint $table) {
            $table->unsignedBigInteger('service_operator_id')
                  ->nullable()
                  ->after('service_id');

            $table->foreign('service_operator_id')
                  ->references('id')
                  ->on('service_operators')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('diesel', function (Blueprint $table) {
            $table->dropForeign(['service_operator_id']);
            $table->dropColumn('service_operator_id');
        });
    }
};
