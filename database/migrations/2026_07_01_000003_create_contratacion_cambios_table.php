<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratacion_cambios', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('contratacion_id')->constrained('contrataciones')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('tipo_cambio', 80);
            $table->date('fecha_cambio');
            $table->string('motivo', 255);
            $table->text('observaciones')->nullable();
            $table->json('datos_anteriores');
            $table->json('datos_nuevos');
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['contratacion_id', 'fecha_cambio']);
            $table->index(['user_id', 'fecha_cambio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratacion_cambios');
    }
};
