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
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->string('nombre')->nullable()->after('id');
            $table->string('tipo_servicio')->nullable()->after('nombre');
            $table->string('color')->nullable()->after('modelo');
            $table->string('tipo_carroceria')->nullable()->after('color');
            $table->string('tipo_combustible')->nullable()->after('tipo_carroceria');
            $table->string('numero_motor')->nullable()->after('tipo_combustible');
            $table->string('numero_chasis')->nullable()->after('numero_motor');
            $table->string('propietario')->nullable()->after('numero_chasis');
            $table->string('identificacion')->nullable()->after('propietario');
            $table->string('organismo_transito')->nullable()->after('identificacion');
            $table->date('fecha_matricula')->nullable()->after('organismo_transito');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropColumn([
                'nombre',
                'tipo_servicio',
                'color',
                'tipo_carroceria',
                'tipo_combustible',
                'numero_motor',
                'numero_chasis',
                'propietario',
                'identificacion',
                'organismo_transito',
                'fecha_matricula'
            ]);
        });
    }
};
