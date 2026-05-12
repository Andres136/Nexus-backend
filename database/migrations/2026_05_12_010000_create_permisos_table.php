<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permisos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('user_id')->constrained('users');

            $table->date('fecha');

            $table->enum('tipo', ['llegada_tarde', 'salida_temprana', 'ausencia_parcial']);

            // Ventana de tiempo no trabajada
            $table->time('hora_inicio');
            $table->time('hora_fin');

            // Si es remunerado → no se descuenta. Si no → se descuenta del salario
            $table->boolean('es_remunerado')->default(false);

            $table->string('motivo')->nullable();

            $table->enum('status', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');

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
        Schema::dropIfExists('permisos');
    }
};
