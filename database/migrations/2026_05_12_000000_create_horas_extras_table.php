<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horas_extras', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->comment('Empleado que realizó las horas extras');

            $table->date('fecha');

            $table->decimal('horas', 6, 2);

            $table->enum('tipo', [
                'diurna',
                'nocturna',
                'festiva',
                'nocturna_festiva',
            ]);

            $table->string('motivo')->nullable();

            $table->enum('status', ['pendiente', 'aprobada', 'rechazada'])
                  ->default('pendiente');

            $table->foreignId('autorizado_por')
                  ->nullable()
                  ->constrained('users')
                  ->comment('Supervisor que aprobó o rechazó');

            $table->timestamp('fecha_gestion')->nullable()
                  ->comment('Cuándo se aprobó o rechazó');

            $table->string('observacion_gestion')->nullable()
                  ->comment('Motivo de rechazo u observación del supervisor');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horas_extras');
    }
};
