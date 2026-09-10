<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('responsable_id')->constrained('users');
            // nullable: se crea la fila con create() y luego se asigna creado_por aparte
            // (no es mass-assignable), igual que DeliveryEvent::creado_por.
            $table->foreignId('creado_por')->nullable()->constrained('users');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->time('hora')->nullable();
            $table->string('estado')->default('programada');
            $table->text('observaciones')->nullable();
            $table->decimal('calificacion_final', 3, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
