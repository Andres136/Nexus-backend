<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vsm_configuracion', function (Blueprint $table) {
            $table->id();
            $table->decimal('meta_unidades_hora', 10, 2)
                  ->comment('Unidades/hora que equivalen al 100% de eficiencia');
            $table->string('descripcion')->nullable()
                  ->comment('Etiqueta descriptiva: ej. Meta estándar 2026');
            $table->boolean('activo')->default(true);
            $table->foreignId('creado_por')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vsm_configuracion');
    }
};
