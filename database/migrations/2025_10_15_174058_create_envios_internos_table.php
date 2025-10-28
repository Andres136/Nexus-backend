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
        Schema::create('envios_internos', function (Blueprint $table) {

            $table->id();
            $table->foreignId('sede_origen_id')->constrained('sedes');
            $table->foreignId('sede_destino_id')->constrained('sedes');
            $table->foreignId('empresa_id')->nullable()->constrained('empresas');
            $table->foreignId('usuario_id')->constrained('users');
            $table->foreignId('estado_id')->nullable()->constrained('estados');
            $table->date('fecha_envio')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('envios_internos');
    }
};
