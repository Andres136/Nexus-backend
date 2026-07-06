<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recuperaciones_tiempo', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('work_session_id')->nullable()->constrained('work_sessions')->nullOnDelete();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->unsignedSmallInteger('minutos_autorizados');
            $table->unsignedSmallInteger('minutos_usados')->default(0);
            $table->string('motivo', 255);
            $table->string('status', 20)->default('aprobada');
            $table->foreignId('autorizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_gestion')->nullable();
            $table->text('observacion_gestion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'fecha', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recuperaciones_tiempo');
    }
};
