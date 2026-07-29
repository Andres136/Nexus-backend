<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomina_lotes_aprobacion', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->date('periodo_inicio');
            $table->date('periodo_fin');
            $table->json('preliquidacion_ids');
            $table->foreignId('responsable_id')->constrained('users');
            $table->foreignId('generado_por')->constrained('users');
            $table->string('estado')->default('pendiente'); // pendiente | aprobado | error_parcial
            $table->timestamp('aprobado_en')->nullable();
            $table->json('resultado')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina_lotes_aprobacion');
    }
};
