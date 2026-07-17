<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alistamiento', function (Blueprint $table) {
            $table->string('nombre_actividad')->nullable()->after('tipo_origen');
        });
    }

    public function down(): void
    {
        Schema::table('alistamiento', function (Blueprint $table) {
            $table->dropColumn('nombre_actividad');
        });
    }
};
