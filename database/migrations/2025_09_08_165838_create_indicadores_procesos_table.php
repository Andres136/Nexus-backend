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
        Schema::create('indicadores_procesos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departamento_id')->constrained('departamentos')->onDelete('cascade');
            $table->string('formula');
            $table->decimal('meta', 10, 2);
            $table->string('frecuencia');
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::table('indicadores_procesos', function (Blueprint $table) {
            $table->dropForeign(['departamento_id']);
            $table->dropForeign(['user_id']);
        });
        Schema::dropIfExists('indicadores_procesos');

    }
};
