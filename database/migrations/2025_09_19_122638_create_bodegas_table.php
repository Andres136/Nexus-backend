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
            Schema::create('bodegas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('direccion');
            
            $table->foreignId('sede_id')
                  ->constrained('sedes')
                  ->onDelete('cascade');

            $table->foreignId('estado_id')
                  ->constrained('estados')
                  ->onDelete('cascade')
                  ->default(3); // 👈 valor por defecto

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::dropIfExists('bodegas');
      
    }
};
