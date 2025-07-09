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
        Schema::create('revision_comparendos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conductor_id')->constrained('datos_conductores')->onDelete('cascade');
            $table->date('fecha_revision');
            $table->string('archivo_soporte'); // Ruta del archivo cargado
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {// Drop the key foreign key constraint before dropping the table

        Schema::table('revision_comparendos', function (Blueprint $table) {
            $table->dropForeign(['conductor_id']);
        });
        Schema::dropIfExists('revision_comparendos');

    }
};
