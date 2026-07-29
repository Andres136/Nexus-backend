<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomina_excepciones_descuento', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->date('periodo_inicio');
            $table->date('periodo_fin');
            $table->boolean('descontar_tardanzas')->default(true);
            $table->boolean('descontar_permisos')->default(true);
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'periodo_inicio', 'periodo_fin'], 'nomina_excepciones_user_periodo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina_excepciones_descuento');
    }
};
