<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable()->after('cliente_id')->constrained('empresas')->nullOnDelete();
            $table->string('empresa')->nullable()->change();
        });

        $empresas = DB::table('empresas')->get(['id', 'nombre']);
        DB::table('cotizaciones')->whereNull('empresa_id')->orderBy('id')->each(function ($cotizacion) use ($empresas) {
            $valor = Str::of((string) $cotizacion->empresa)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '')->toString();
            $empresa = $empresas->first(function ($item) use ($valor) {
                $nombre = Str::of($item->nombre)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '')->toString();
                return $nombre === $valor || str_contains($nombre, $valor) || str_contains($valor, $nombre);
            });
            if ($empresa) DB::table('cotizaciones')->where('id', $cotizacion->id)->update(['empresa_id' => $empresa->id, 'empresa' => $empresa->nombre]);
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('empresa_id');
        });
    }
};
