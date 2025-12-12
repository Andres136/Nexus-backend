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
        Schema::create('alistamiento_usuario', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('alistamiento_id');
            $table->unsignedBigInteger('usuario_id');

            $table->foreign('alistamiento_id')->references('id')->on('alistamiento')->onDelete('cascade');
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('estado')->default('EN_PROGRESO');
            $table->dateTime('inicio')->nullable();
            $table->dateTime('pausado_en')->nullable();
            $table->integer('tiempo_segundos')->default(0);
            $table->text('razon')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alistamiento_usuario');
    }
};
