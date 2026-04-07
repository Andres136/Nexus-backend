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
        Schema::create('gestion_cartera_soportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('historial_id')->references('id')->on('gestion_cartera_historial')->onDelete('cascade');
            $table->string('archivo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gestion_cartera_soportes');
    }
};
