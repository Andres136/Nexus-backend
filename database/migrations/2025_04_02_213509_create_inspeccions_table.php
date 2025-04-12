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
        Schema::create('inspeccions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->onDelete('cascade');
            $table->date('fecha')->nullable();
            $table->string('responsable')->nullable();
            $table->text('observaciones')->nullable();
            $table->enum('estado_general', ['Aprobado', 'Requiere mantenimiento', 'No apto']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the foreign key constraint first
        Schema::table('inspeccions', function (Blueprint $table) {
            $table->dropForeign(['vehiculo_id']);
        });
        // Drop the table
        Schema::dropIfExists('inspeccions');
    }
};
