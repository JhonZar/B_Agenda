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
        Schema::create('paralelo_estudiante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paralelos_id')
                ->constrained('paralelos')
                ->cascadeOnDelete();
            // FK al estudiante (users.id con rol 'student')
            $table->foreignId('student_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['paralelos_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paralelo_estudiante');
    }
};
