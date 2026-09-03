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
        Schema::table('convocatoria_cuestionarios', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        DB::table('convocatoria_cuestionarios')->whereNull('uuid')->get(['id'])->each(function ($fila) {
            DB::table('convocatoria_cuestionarios')->where('id', $fila->id)->update(['uuid' => Str::uuid()->toString()]);
        });
    }

    public function down(): void
    {
        Schema::table('convocatoria_cuestionarios', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
