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
        Schema::create('paralelos', function (Blueprint $table) {
            $table->id();
            $table->string('grade');       // p. ej. “Primero de Primaria” o “Segundo de Secundaria”
            $table->string('section');     // p. ej. “A”, “B”, “C”
            // FK al profesor encargado de este paralelo (users.id con rol 'teacher')
            $table->foreignId('teacher_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['grade', 'section']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paralelos');
    }
};
