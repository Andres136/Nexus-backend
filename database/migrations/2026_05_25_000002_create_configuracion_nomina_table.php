<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_nomina', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nombre', 120)->default('Configuración general');
            $table->decimal('porcentaje_salud_empleado', 5, 2)->default(4.00);
            $table->decimal('porcentaje_pension_empleado', 5, 2)->default(4.00);
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_nomina');
    }
};
