<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('approvable_type');
            $table->unsignedBigInteger('approvable_id');
            $table->string('kind', 64);
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comment')->nullable();
            $table->json('snapshot')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('status', 'idx_approvals_status');
            $table->index(['approvable_type', 'approvable_id'], 'idx_approvals_morph');
            $table->index('kind', 'idx_approvals_kind');

            $table->foreign('requested_by', 'fk_approvals_requested_by')->references('id')->on('users');
            $table->foreign('reviewed_by', 'fk_approvals_reviewed_by')->references('id')->on('users');
        });

        // Añadir columna virtual generada y unique constraint
        DB::statement('ALTER TABLE approvals ADD COLUMN is_open TINYINT(1) GENERATED ALWAYS AS (CASE WHEN status = "pending" THEN 1 ELSE NULL END) VIRTUAL');
        DB::statement('CREATE UNIQUE INDEX uniq_approvals_open ON approvals (approvable_type, approvable_id, kind, scope_id, is_open)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
