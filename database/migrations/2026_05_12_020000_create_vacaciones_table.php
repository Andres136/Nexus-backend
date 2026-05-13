<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacaciones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('user_id')->constrained('users');

            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->integer('dias_habiles');

            $table->enum('tipo', ['ordinarias', 'compensadas'])->default('ordinarias');
            // ordinarias = se disfrutan, compensadas = se pagan en dinero

            $table->string('motivo')->nullable();

            $table->enum('status', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');

            $table->foreignId('autorizado_por')
                  ->nullable()
                  ->constrained('users');

            $table->timestamp('fecha_gestion')->nullable();
            $table->string('observacion_gestion')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacaciones');
    }
};
