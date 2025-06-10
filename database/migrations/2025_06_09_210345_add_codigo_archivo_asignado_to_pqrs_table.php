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
        Schema::table('pqrs', function (Blueprint $table) {
            $table->string('codigo_radicado')->unique()->after('id');
            $table->string('archivo')->nullable()->after('mensaje');
            $table->unsignedBigInteger('asignado_a')->nullable()->after('estado_id');

            $table->foreign('asignado_a')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        //Eliminamos las columnas en reversa
        Schema::table('pqrs', function (Blueprint $table) {
            $table->dropForeign(['asignado_a']);
            $table->dropColumn(['codigo_radicado', 'archivo', 'asignado_a']);
        });
        Schema::table('pqrs', function (Blueprint $table) {
            //
        });
    }
};
