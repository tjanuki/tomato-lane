<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A project is a user's name for one palette colour. Tasks carry the
     * colour; the project supplies the label shown on the card.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('color', 20);
            $table->string('name', 100);
            $table->timestampsTz();

            $table->unique(['user_id', 'color']);
        });

        DB::statement("ALTER TABLE projects ADD CONSTRAINT projects_color_check CHECK (color IN ('white', 'yellow', 'green', 'blue', 'purple', 'red', 'orange', 'magenta', 'cyan', 'brown'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
