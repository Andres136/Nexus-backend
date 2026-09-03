<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postulaciones_convocatoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convocatoria_id')->constrained('convocatorias')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('cargo_interes');
            $table->timestamps();

            $table->unique(['convocatoria_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postulaciones_convocatoria');
    }
};
