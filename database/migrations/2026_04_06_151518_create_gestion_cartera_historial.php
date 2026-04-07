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
  Schema::create('gestion_cartera_historial', function (Blueprint $table) {
    $table->id();

    $table->foreignId('gestion_cartera_id')->references('id')->on('gestion_cartera')->onDelete('cascade');

    $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');

  
    $table->text('observacion')->nullable();

    $table->enum('tipo', [
        'LLAMADA',
        'EMAIL',
        'VISITA',
        'PROMESA_PAGO',
        'OTRO'
    ])->default('OTRO');

    $table->date('fecha_compromiso')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gestion_cartera_historial');
    }
};
