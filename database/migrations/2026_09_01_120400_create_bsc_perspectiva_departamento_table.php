<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Departamentos responsables de una perspectiva del BSC (1..N).
     * Ej: Financiera -> Contabilidad + Gerencia. Es informativo/organizacional:
     * los indicadores siguen trayendo su propio departamento_id.
     */
    public function up(): void
    {
        Schema::create('bsc_perspectiva_departamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perspectiva_id')->constrained('bsc_perspectivas')->cascadeOnDelete();
            $table->foreignId('departamento_id')->constrained('departamentos')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['perspectiva_id', 'departamento_id'], 'bsc_persp_depto_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bsc_perspectiva_departamento');
    }
};
