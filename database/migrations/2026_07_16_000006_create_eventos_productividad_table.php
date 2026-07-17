<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos_productividad', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('jornada_operativa_id')->nullable()->constrained('jornadas_operativas')->nullOnDelete();
            $table->foreignId('actividad_operativa_id')->nullable()->constrained('actividades_operativas')->nullOnDelete();
            $table->string('tipo_evento');
            $table->nullableMorphs('origen');
            $table->dateTime('ocurrio_at');
            $table->text('resumen')->nullable();
            $table->json('metricas')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'ocurrio_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_productividad');
    }
};
