<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // `nombre` es el texto visible (se puede renombrar desde la UI); `codigo` es
        // un slug estable que el código usa para identificar la responsabilidad
        // (antes se buscaba por nombre, frágil: renombrar rompía las notificaciones
        // en silencio). Se genera una vez al crear el registro y no cambia más.
        Schema::table('responsabilidades', function (Blueprint $table) {
            $table->string('codigo')->nullable()->unique()->after('nombre');
        });

        DB::table('responsabilidades')->whereNull('codigo')->get()->each(function ($responsabilidad) {
            DB::table('responsabilidades')
                ->where('id', $responsabilidad->id)
                ->update(['codigo' => Str::slug($responsabilidad->nombre, '_')]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('responsabilidades', function (Blueprint $table) {
            $table->dropColumn('codigo');
        });
    }
};
