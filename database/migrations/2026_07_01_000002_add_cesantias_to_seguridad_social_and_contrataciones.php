<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE seguridad_socials MODIFY tipo ENUM('eps','arl','afp','ccf','cesantias') NULL");

        Schema::table('contrataciones', function (Blueprint $table) {
            if (! Schema::hasColumn('contrataciones', 'fondo_cesantias_id')) {
                $table->foreignId('fondo_cesantias_id')
                    ->nullable()
                    ->after('caja_penciones_id')
                    ->constrained('seguridad_socials')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contrataciones', function (Blueprint $table) {
            if (Schema::hasColumn('contrataciones', 'fondo_cesantias_id')) {
                $table->dropConstrainedForeignId('fondo_cesantias_id');
            }
        });

        DB::table('seguridad_socials')->where('tipo', 'cesantias')->update(['tipo' => null]);
        DB::statement("ALTER TABLE seguridad_socials MODIFY tipo ENUM('eps','arl','afp','ccf') NULL");
    }
};
