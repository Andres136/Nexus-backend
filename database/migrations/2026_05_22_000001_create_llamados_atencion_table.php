<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('llamados_atencion', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('contratacion_id')->nullable()->constrained('contrataciones')->nullOnDelete();
            $table->string('tipo', 50); // tardanza, almuerzo, ausencia, otro
            $table->string('titulo', 150)->nullable();
            $table->text('detalle')->nullable();
            $table->integer('minutos')->default(0);
            $table->date('fecha_hecho');
            $table->string('severidad', 20)->default('leve'); // leve, moderado, grave
            $table->foreignId('generado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('llamados_atencion');
    }
};
