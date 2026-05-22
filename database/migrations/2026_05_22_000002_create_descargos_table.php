<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descargos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('contratacion_id')->nullable()->constrained('contrataciones')->nullOnDelete();
            $table->foreignId('llamado_atencion_id')->nullable()->constrained('llamados_atencion')->nullOnDelete();
            $table->string('tipo_descargo', 150);
            $table->date('fecha_hecho');
            $table->text('descripcion');
            $table->foreignId('generado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descargos');
    }
};
