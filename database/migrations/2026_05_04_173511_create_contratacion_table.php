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
       Schema::create('contratacion', function (Blueprint $table) {
       $table->id();
       $table->uuid('uuid')->unique();
       $table->foreignId('id_contrato')->constrained('tipo_contratos');
       $table->foreignId('users_id')->constrained('users');
       $table->decimal('no_salarial', 10, 2);
       $table->decimal('base_salario', 10, 2);
       $table->decimal('auxilio_transporte', 10, 2)->default(0);
       $table->integer('pago_frecuencia');
       $table->date('inicio_contratacion');
       $table->datetime('fin_contrato')->nullable();
       $table->tinyInteger('status')->default(1);
       $table->integer('eps');
       $table->integer('arl_id');
       $table->integer('fondo_pensiones');
       $table->integer('caja_penciones_id');
       $table->timestamps();
       $table->softDeletes();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratacion');
    }
};
