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
        Schema::create('paralelo_curso_materia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paralelos_id')
                ->constrained('paralelos')
                ->cascadeOnDelete();
            // FK a la materia
            $table->foreignId('materias_id')
                ->constrained('materias')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['paralelos_id', 'materias_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paralelo_curso_materia');
    }
};
