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
        Schema::table('novedad_diaria', function (Blueprint $table) {
                 $table->enum('estado', ['ABIERTA', 'EN_PROCESO', 'CERRADA'])
              ->default('ABIERTA');
      

        $table->date('fecha_revision')
              ->nullable();
           

        $table->date('fecha_terminado')
              ->nullable();
     

        $table->string('soporte')
              ->nullable();
        

                   $table->foreignId('responsable_id')
              ->nullable()
              ->constrained('users')
              ->onDelete('set null');
 
        });

   
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('novedad_diaria', function (Blueprint $table) {
            $table->dropColumn(['estado', 'fecha_revision', 'fecha_terminado', 'soporte', 'responsable_id']);
        });
    }
};
