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
        Schema::create('residuos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_residuo_id')->constrained('tipo_residuos')->onDelete('cascade');
            $table->foreignId('sede_id')->references('id')->on('sedes')->onDelete('cascade');
            $table->decimal('cantidad', 10, 2);
            $table->date('fecha');
            $table->string('unidad_medida');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('residuos');
    }
};
