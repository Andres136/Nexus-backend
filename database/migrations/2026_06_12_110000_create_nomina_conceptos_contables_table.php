<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomina_conceptos_contables', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('codigo', 80)->unique();
            $table->string('nombre', 160);
            $table->enum('tipo', ['devengo', 'deduccion', 'aporte_empleador', 'provision', 'neto']);
            $table->foreignId('puck_id')->nullable()->constrained('puck')->nullOnDelete();
            $table->enum('naturaleza', ['debito', 'credito']);
            $table->boolean('requiere_tercero')->default(true);
            $table->boolean('requiere_centro_costo')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipo', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina_conceptos_contables');
    }
};
