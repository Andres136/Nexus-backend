<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('work_sessions', 'minutos_almuerzo')) {
                $table->integer('minutos_almuerzo')->default(0)->after('minutos_pausa');
            }
        });
    }

    public function down(): void
    {
        Schema::table('work_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('work_sessions', 'minutos_almuerzo')) {
                $table->dropColumn('minutos_almuerzo');
            }
        });
    }
};
