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
        Schema::create('analisis_productos_no_conformes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_no_conforme_id')->constrained('productos_no_conformes');
            $table->foreignId('analista_id')->constrained('users');
            $table->date('fecha_analisis');
            $table->text('causa_raiz');
            $table->text('acciones_correctivas')->nullable();
            $table->text('acciones_preventivas')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('estado_id')->constrained('estados');
            $table->date('fecha_cierre')->nullable();
            $table->string('archivo_evidencia')->nullable();
            $table->foreignId('responsable_cierre_id')->nullable()->constrained('users');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analisis_productos_no_conformes');
    }
};
