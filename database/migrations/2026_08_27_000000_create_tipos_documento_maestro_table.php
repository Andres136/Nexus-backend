<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_documento_maestro', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->timestamps();
        });

        // Primero conservamos exactamente los valores ya usados; los valores
        // predeterminados solo completan el catálogo sin modificar documentos.
        $nombres = DB::table('documentos_maestros')
                ->whereNotNull('tipo_documento')
                ->where('tipo_documento', '<>', '')
                ->pluck('tipo_documento')
            ->merge([
                'FORMATO',
                'POLITICA',
                'PROCEDIMIENTO',
                'MANUAL',
                'OTRO DOCUMENTO',
            ])
            ->map(fn ($nombre) => trim($nombre))
            ->filter()
            ->unique()
            ->values();

        $ahora = now();
        DB::table('tipos_documento_maestro')->insertOrIgnore(
            $nombres->map(fn ($nombre) => [
                'nombre' => $nombre,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ])->all()
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_documento_maestro');
    }
};
