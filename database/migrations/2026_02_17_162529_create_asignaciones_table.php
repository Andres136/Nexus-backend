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
        Schema::create('asignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->constrained('users')->onDelete('cascade');
            $table->foreignId('sede_id')->constrained('sedes')->onDelete('cascade');
         $table->foreignId('producto_id')
        ->constrained('products')
        ->onDelete('cascade');
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->date('fecha_asignacion');
            $table->date('fecha_devolucion')->nullable();
            $table->foreignId('usuario_asignacion_id')->constrained('users')->onDelete('cascade');
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            
    $table->unique(['producto_id', 'activo']);


            $table->timestamps();
            $table->index('producto_id');
                $table->index('id_usuario');
                $table->index('sede_id');
                $table->index('empresa_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asignaciones');
    }
};
