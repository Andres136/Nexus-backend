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
        Schema::create('documento_vehiculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->onDelete('cascade');
            $table->string('tipo_documento'); // Tipo de documento (ej. SOAT, revisión técnico-mecánica, etc.)
            $table->date('fecha_vencimiento'); // Fecha de vencimiento del documento
            $table->date('fecha_renovacion')->nullable(); // Fecha de renovación del documento
            $table->string('documento_pdf')->nullable(); // Ruta del archivo PDF del documento
            $table->enum('estado', ['Vigente', 'Por vencer', 'Vencido'])->default('Vigente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the foreign key constraint first
        Schema::table('documento_vehiculos', function (Blueprint $table) {
            $table->dropForeign(['vehiculo_id']);
        });
        Schema::dropIfExists('documento_vehiculos');
    }
};
