<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cabecera de una ronda de verificación de eficacia de una no
        // conformidad (novedad_diaria). El detalle, con la calificación por
        // cada hallazgo vinculado, va en eficacia_evaluacion_hallazgos.
        Schema::create('eficacia_evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('novedad_id')->constrained('novedad_diaria')->cascadeOnDelete();
            $table->foreignId('verificador_id')->constrained('users')->cascadeOnDelete();
            // Veredicto final de la Novedad completa. Se calcula solo, a
            // partir del promedio de las calificaciones 1-5 de cada hallazgo
            // (ver EficaciaEvaluacionService::registrar) — no se pide a mano.
            $table->enum('resultado', ['eficaz', 'parcial', 'no_eficaz']);
            $table->text('observacion');
            $table->date('proxima_verificacion')->nullable();
            $table->timestamps();
        });

        Schema::create('eficacia_evaluacion_hallazgos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eficacia_evaluacion_id')->constrained('eficacia_evaluaciones')->cascadeOnDelete();
            $table->foreignId('hallazgo_id')->constrained('hallazgo_novedades')->cascadeOnDelete();
            // Calificación individual del hallazgo, 1 a 5 estrellas.
            $table->unsignedTinyInteger('calificacion');
            $table->timestamps();

            $table->unique(['eficacia_evaluacion_id', 'hallazgo_id'], 'eficacia_eval_hallazgo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eficacia_evaluacion_hallazgos');
        Schema::dropIfExists('eficacia_evaluaciones');
    }
};
