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
        Schema::create('alistamiento_usuario_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alistamiento_id')->references('id')->on('alistamiento')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('detalle_id')->constrained('alistamiento_detalles')->cascadeOnDelete();

            $table->integer('cantidad_alistada')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alistamiento_usuario_detalles');
    }
};
