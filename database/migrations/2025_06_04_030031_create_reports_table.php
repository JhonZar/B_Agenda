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
        Schema::create('reportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            // FK al profesor que reporta (users.id, role = 'teacher')
            $table->foreignId('teacher_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            // FK a la categoría de reporte
            $table->foreignId('category_id')
                  ->constrained('categorias_reportes')
                  ->cascadeOnDelete();
            $table->text('description')->nullable(); // Detalle del reporte
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reportes');
    }
};
