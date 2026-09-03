<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Marca al usuario como asesor externo (no tiene contrato de nómina).
            // Sirve para incluirlo en la lista de firmantes de las actas de capacitación.
            $table->boolean('es_asesor_externo')->default(false)->after('role_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('es_asesor_externo');
        });
    }
};
