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
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->string('title');   // Título breve de la notificación
            $table->text('body');      // Contenido más extenso
            $table->boolean('via_whatsapp')
                  ->default(false);   // Si además se envió por WhatsApp
            $table->dateTime('sent_at')->nullable(); // Fecha en que se envió (app o WhatsApp)
            $table->dateTime('read_at')->nullable(); // Fecha en que el usuario marcó como leído
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
