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
        Schema::create('datos_conductores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
             $table->string('cedula')->unique(); // ✅ corregido
            $table->string('licencia_conduccion')->unique(); // ✅ corregido
            $table->string('tipo_licencia');
            $table->date('fecha_expedicion');
            $table->date('fecha_vencimiento');
            $table->string('categoria');
            $table->string('grupo_sanguineo')->nullable();
            $table->string('rut_archivo')->nullable(); // Ruta del archivo de la licencia
            $table->string('licencia_archivo')->nullable(); // Ruta del archivo de la licencia de conducción
            $table->string('comparendo_archivo')->nullable(); // Ruta del archivo del comparendo

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {


        //Eliminar llaves foráneas
        Schema::table('datos_conductores', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::dropIfExists('datos_conductores', function (Blueprint $table) {
            $table->dropIfExists('cedula');
            $table->dropIfExists('licencia_conduccion');
            $table->dropIfExists('tipo_licencia');
            $table->dropIfExists('fecha_expedicion');
            $table->dropIfExists('fecha_vencimiento');
            $table->dropIfExists('categoria');
            $table->dropIfExists('grupo_sanguineo');
            $table->dropIfExists('rut_archivo');
            $table->dropIfExists('licencia_archivo');
            $table->dropIfExists('comparendo_archivo');
        });

    }
};
