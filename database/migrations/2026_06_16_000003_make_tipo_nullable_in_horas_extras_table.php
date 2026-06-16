<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('horas_extras', function (Blueprint $table) {
            $table->enum('tipo', [
                'diurna',
                'nocturna',
                'festiva',
                'nocturna_festiva',
            ])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('horas_extras', function (Blueprint $table) {
            $table->enum('tipo', [
                'diurna',
                'nocturna',
                'festiva',
                'nocturna_festiva',
            ])->nullable(false)->change();
        });
    }
};
