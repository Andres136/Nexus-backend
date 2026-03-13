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
        Schema::create('inspecciones_hseq', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->references('id')->on('sedes')->onDelete('cascade');
            $table->foreignId('tipo_inspeccion_id')->references('id')->on('tipo_inspecciones')->onDelete('cascade');
            $table->date('fecha');
            $table->foreignId('responsable_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('estado')->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspecciones');
    }
};
