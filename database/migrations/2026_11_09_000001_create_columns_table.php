<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('kind', 10)->default('list');
            $table->integer('position')->default(0);
            $table->smallInteger('wip_limit')->nullable();
            $table->timestampsTz();

            $table->index(['user_id', 'position']);
        });

        // Values are frozen here on purpose: a later change to the ColumnKind
        // enum needs its own migration, not a silent edit of this one.
        DB::statement("ALTER TABLE columns ADD CONSTRAINT columns_kind_check CHECK (kind IN ('list', 'day', 'done'))");
        DB::statement('ALTER TABLE columns ADD CONSTRAINT columns_wip_limit_check CHECK (wip_limit IS NULL OR wip_limit > 0)');

        // Partial unique index: each user has at most one Done column.
        DB::statement("CREATE UNIQUE INDEX columns_one_done_per_user ON columns (user_id) WHERE kind = 'done'");
    }

    public function down(): void
    {
        Schema::dropIfExists('columns');
    }
};
