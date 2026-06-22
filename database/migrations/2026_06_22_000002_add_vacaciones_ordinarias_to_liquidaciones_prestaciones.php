<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE liquidaciones_prestaciones MODIFY tipo "
                ."ENUM('prima','cesantias','vacaciones_ordinarias','vacaciones_compensadas') NOT NULL"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE liquidaciones_prestaciones MODIFY tipo "
                ."ENUM('prima','cesantias','vacaciones_compensadas') NOT NULL"
            );
        }
    }
};
