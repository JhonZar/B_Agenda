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
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('paralelo_id')
                ->constrained('paralelos')
                ->cascadeOnDelete();
            $table->date('fecha');  // Día de la asistencia
            $table->enum('estado', ['presente', 'ausente', 'tarde'])
                ->default('presente');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
