<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->date('fecha_entrega')->nullable()->after('prioridad');
            $table->time('hora_entrega')->nullable()->after('fecha_entrega');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['fecha_entrega', 'hora_entrega']);
        });
    }
};
