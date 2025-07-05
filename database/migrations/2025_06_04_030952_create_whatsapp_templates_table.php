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
        Schema::create('plantillas_whatsapp', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();    // Nombre interno de la plantilla
            $table->text('content');             // Texto con variables tipo {{var}}
            $table->foreignId('created_by')       // El admin que creó/edita la plantilla
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantillas_whatsapp');
    }
};
