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
        Schema::create('gestion_cartera_pivote', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gestion_cartera_id')->references('id')->on('gestion_cartera')->onDelete('cascade');
            $table->decimal('valor_pago', 15, 2);
            $table->date('fecha_pago');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gestion_cartera_pivote');
    }
};
