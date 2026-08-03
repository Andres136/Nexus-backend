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
        Schema::create('cartera_gestion_mensual', function (Blueprint $table) {
            $table->id();
            $table->integer('anio');
            $table->integer('mes');
            $table->unsignedInteger('cartera_vencidas');
            $table->unsignedInteger('cartera_gestionadas');
            $table->decimal('cartera_pct_gestion', 5, 2);
            $table->timestamps();

            $table->unique(['anio', 'mes']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cartera_gestion_mensual');
    }
};
