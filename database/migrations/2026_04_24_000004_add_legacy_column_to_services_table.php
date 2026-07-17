<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // DEFAULT 1 asigna legacy=1 a todos los registros existentes automáticamente
            $table->tinyInteger('legacy')->default(1)->after('substate_id');
            $table->index('legacy');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['legacy']);
            $table->dropColumn('legacy');
        });
    }
};
