<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licencias', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('user_id')->constrained('users');

            $table->enum('tipo', ['maternidad', 'paternidad']);
            $table->date('inicio');
            $table->date('fin');
            $table->unsignedSmallInteger('dias_calendario');

            $table->string('soporte')->nullable();
            $table->text('motivo')->nullable();

            $table->enum('status', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');

            $table->foreignId('autorizador_id')->nullable()->constrained('users');
            $table->text('observacion')->nullable();
            $table->timestamp('fecha_gestion')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licencias');
    }
};
