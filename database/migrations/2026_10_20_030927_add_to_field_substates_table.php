<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('substates', 'status')) {
            Schema::table('substates', function (Blueprint $table) {
                $table->tinyInteger('status')->default(1)->after('zombie');
            });
        }

        DB::table('substates')
            ->whereIn('id', [4, 5, 10, 13, 14, 17])
            ->update(['status' => 0]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('substates', 'status')) {
            Schema::table('substates', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
