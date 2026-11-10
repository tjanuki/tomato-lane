<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // NO ACTION (the default): deleting a column that still holds cards
            // fails, but the check waits until the end of the statement, so a
            // user-account delete can cascade through columns and tasks
            // together. RESTRICT would check immediately and break that.
            $table->foreignId('column_id')->constrained();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 20)->default('white');
            $table->integer('position')->default(0);
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();

            $table->index(['column_id', 'position']);
            $table->index('user_id');
        });

        DB::statement("ALTER TABLE tasks ADD CONSTRAINT tasks_color_check CHECK (color IN ('white', 'yellow', 'green', 'blue', 'purple', 'red', 'orange', 'magenta', 'cyan', 'brown'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
