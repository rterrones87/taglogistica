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
        Schema::table('treasury_services', function (Blueprint $table) {
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('user_id');
            $table->foreign('reviewed_by')->references('id')->on('users');
        });

        Schema::table('treasury_maintenances', function (Blueprint $table) {
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('user_id');
            $table->foreign('reviewed_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('treasury_services', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn('reviewed_by');
        });

        Schema::table('treasury_maintenances', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn('reviewed_by');
        });
    }
};
