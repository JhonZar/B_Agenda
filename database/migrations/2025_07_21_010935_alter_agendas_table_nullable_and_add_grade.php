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
        Schema::table('agendas', function (Blueprint $table) {
            $table->unsignedBigInteger('paralelo_id')->nullable()->change();

            // Añadir campo grade después de paralelo_id
            $table->string('grade')
                ->nullable()
                ->after('paralelo_id')
                ->comment('Curso/Grado del evento, e.g. "3ro Sec."');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn('grade');

            // Restaurar paralelo_id NOT NULL
            $table->unsignedBigInteger('paralelo_id')->nullable(false)->change();
        });
    }
};
