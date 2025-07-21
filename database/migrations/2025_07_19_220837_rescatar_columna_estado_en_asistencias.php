<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones.
     */
    public function up(): void
    {
        // Si no existe 'estado', la creamos; si existe, la modificamos inline
        if (! Schema::hasColumn('asistencias', 'estado')) {
            Schema::table('asistencias', function (Blueprint $table) {
                $table->enum('estado', [
                    'presente',
                    'ausente',
                    'tarde',
                    'justificado',
                ])
                ->default('presente')
                ->after('fecha');
            });
        } else {
            Schema::table('asistencias', function (Blueprint $table) {
                $table->enum('estado', [
                    'presente',
                    'ausente',
                    'tarde',
                    'justificado',
                ])
                ->default('presente')
                ->change();
            });
        }

        // Asegurarnos de que los demás campos existan (hora_llegada, notas, created_by)
        if (! Schema::hasColumn('asistencias', 'hora_llegada')) {
            Schema::table('asistencias', function (Blueprint $table) {
                $table->time('hora_llegada')->nullable()->after('estado');
            });
        }

        if (! Schema::hasColumn('asistencias', 'notas')) {
            Schema::table('asistencias', function (Blueprint $table) {
                $table->text('notas')->nullable()->after('hora_llegada');
            });
        }

        if (! Schema::hasColumn('asistencias', 'created_by')) {
            Schema::table('asistencias', function (Blueprint $table) {
                $table->foreignId('created_by')
                      ->nullable()
                      ->constrained('users')
                      ->onDelete('SET NULL')
                      ->after('paralelo_id');
            });
        }
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        // (Opcional) aquí podrías revertir o dejar vacío
    }
};
