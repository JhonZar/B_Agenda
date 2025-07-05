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
        Schema::create('codigos_otp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('padre_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->string('codigo', 6);        // p. ej. “123456”
            $table->dateTime('expira_en'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('codigos_otp');
    }
};
