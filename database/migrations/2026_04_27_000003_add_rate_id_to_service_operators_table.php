<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_operators', function (Blueprint $table) {
            $table->unsignedBigInteger('rate_id')->nullable()->after('service_operator_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('service_operators', function (Blueprint $table) {
            $table->dropColumn('rate_id');
        });
    }
};
