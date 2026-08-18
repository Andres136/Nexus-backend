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
        Schema::create('producto_no_conforme_archivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_no_conforme_id')
                ->references('id')
                ->on('productos_no_conformes')
                ->onDelete('cascade');

            $table->string('archivo');
            $table->string('tipo')->nullable();
            $table->string('descripcion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto_no_conforme_archivos');
    }
};
