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
        Schema::create('alistamiento_detalles', function (Blueprint $table) {
            $table->id();
            //Relacion con Alistamiento
            $table->unsignedBigInteger('alistamiento_id');
            $table->foreign('alistamiento_id')
                ->references('id')->on('alistamiento')
                ->onDelete('cascade');

            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->integer('cantidad_programada')->nullable();
            $table->integer('cantidad_alistada')->default(0);
            $table->integer('cantidad_faltante')->nullable();
            $table->integer('tiempo_parcial_segundos')
                ->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alistamiento_detalles');
    }
};
