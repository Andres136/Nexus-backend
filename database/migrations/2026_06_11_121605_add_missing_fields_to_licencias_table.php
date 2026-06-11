<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('licencias', 'motivo')) {
            Schema::table('licencias', function (Blueprint $table) {
                $table->text('motivo')->nullable()->after('soporte');
            });
        }

        if (!Schema::hasColumn('licencias', 'autorizador_id')) {
            Schema::table('licencias', function (Blueprint $table) {
                $table->foreignId('autorizador_id')
                    ->nullable()
                    ->after('status')
                    ->constrained('users');
            });
        }

        if (!Schema::hasColumn('licencias', 'observacion')) {
            Schema::table('licencias', function (Blueprint $table) {
                $table->text('observacion')->nullable()->after('autorizador_id');
            });
        }

        if (!Schema::hasColumn('licencias', 'fecha_gestion')) {
            Schema::table('licencias', function (Blueprint $table) {
                $table->timestamp('fecha_gestion')->nullable()->after('observacion');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('licencias', 'autorizador_id')) {
            Schema::table('licencias', function (Blueprint $table) {
                $table->dropConstrainedForeignId('autorizador_id');
            });
        }

        foreach (['fecha_gestion', 'observacion', 'motivo'] as $column) {
            if (Schema::hasColumn('licencias', $column)) {
                Schema::table('licencias', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
