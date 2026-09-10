<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salidas_temporales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_id')->constrained('asignaciones')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->text('motivo')->nullable();
            $table->dateTime('fecha_salida');
            $table->date('fecha_retorno_estimada')->nullable();
            $table->dateTime('fecha_retorno_real')->nullable();
            $table->text('observaciones_retorno')->nullable();
            $table->string('estado', 20)->default('salida');
            $table->foreignId('creada_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salidas_temporales');
    }
};
