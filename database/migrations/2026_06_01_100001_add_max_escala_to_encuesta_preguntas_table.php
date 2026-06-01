<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encuesta_preguntas', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_escala')->default(5)->after('requerida');
        });
    }

    public function down(): void
    {
        Schema::table('encuesta_preguntas', function (Blueprint $table) {
            $table->dropColumn('max_escala');
        });
    }
};
