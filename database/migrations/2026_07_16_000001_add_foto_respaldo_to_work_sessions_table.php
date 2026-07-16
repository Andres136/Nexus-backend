<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('work_sessions', 'foto_respaldo')) {
                $table->string('foto_respaldo')->nullable()->after('hora_entrada');
            }
        });
    }

    public function down(): void
    {
        Schema::table('work_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('work_sessions', 'foto_respaldo')) {
                $table->dropColumn('foto_respaldo');
            }
        });
    }
};
