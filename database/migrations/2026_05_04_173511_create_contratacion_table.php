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
       Schema::create('contrataciones', function (Blueprint $table) {
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
       $table->foreignId('eps_id')->constrained('seguridad_socials');
       $table->foreignId('arl_id')->constrained('seguridad_socials');
       $table->foreignId('fondo_pensiones_id')->constrained('seguridad_socials');
       $table->foreignId('caja_penciones_id')->constrained('seguridad_socials');
       $table->timestamps();
       $table->softDeletes();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contrataciones');
    }
};
