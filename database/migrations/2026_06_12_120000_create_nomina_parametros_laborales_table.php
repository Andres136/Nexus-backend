<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomina_parametros_laborales', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->year('anio');
            $table->date('fecha_vigencia');
            $table->decimal('salario_minimo', 14, 2);
            $table->decimal('auxilio_transporte', 14, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['anio', 'fecha_vigencia']);
        });

        Schema::table('contrataciones', function (Blueprint $table) {
            $table->enum('tipo_salario', ['salario_minimo', 'personalizado'])
                ->default('personalizado')
                ->after('cargo');
            $table->foreignId('parametro_laboral_id')
                ->nullable()
                ->after('tipo_salario')
                ->constrained('nomina_parametros_laborales')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contrataciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parametro_laboral_id');
            $table->dropColumn('tipo_salario');
        });

        Schema::dropIfExists('nomina_parametros_laborales');
    }
};
