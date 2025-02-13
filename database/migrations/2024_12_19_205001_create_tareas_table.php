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
        Schema::create('tareas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre',255);
            $table->text('descripcion');
            $table->foreignId('departamento_id')->constrained()->onDelete('cascade');
            $table->foreignId('estado_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.dropIfExists
     */
    public function down(): void
    {
        Schema::table('tareas', function (Blueprint $table) {
            // Eliminar claves foráneas
            $table->dropForeign(['departamento_id']);
            $table->dropForeign(['estado_id']);
            $table->dropForeign(['user_id']);
        
            // Eliminar columnas
            $table->dropColumn(['departamento_id', 'estado_id', 'user_id', 'nombre', 'descripcion']);
        });
        
        // Luego, eliminar la tabla si es necesario
        Schema::dropIfExists('tareas');
        
    }
};
