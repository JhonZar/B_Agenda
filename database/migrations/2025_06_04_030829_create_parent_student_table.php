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
        Schema::create('padre_estudiante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('padre_id')
                ->constrained('users')
                ->cascadeOnDelete();
            // FK al estudiante (users.id con rol 'student')
            $table->foreignId('estudiante_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['padre_id', 'estudiante_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('padre_estudiante');
    }
};
