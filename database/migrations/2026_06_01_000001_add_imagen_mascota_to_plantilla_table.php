<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plantilla', function (Blueprint $table) {
            $table->string('imagen_mascota')->nullable()->after('imagen_principal');
        });
    }

    public function down(): void
    {
        Schema::table('plantilla', function (Blueprint $table) {
            $table->dropColumn('imagen_mascota');
        });
    }
};
