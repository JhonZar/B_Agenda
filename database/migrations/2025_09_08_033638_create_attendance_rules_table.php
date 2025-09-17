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
        Schema::create('attendance_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('paralelo_id')->nullable()->index();
            $table->time('entrada');                  
            $table->unsignedSmallInteger('tolerancia_min')->default(0); 
            $table->boolean('activo')->default(true);
            $table->foreign('paralelo_id')->references('id')->on('paralelos')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_rules');
    }
};
