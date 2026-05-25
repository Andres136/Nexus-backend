<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incapacidades', function (Blueprint $table) {
            $table->enum('estado_revision', ['pendiente', 'aprobada', 'rechazada'])
                ->default('pendiente')
                ->after('user_reviso_id');
            $table->text('observacion_revision')->nullable()->after('estado_revision');
            $table->timestamp('fecha_revision')->nullable()->after('observacion_revision');
        });
    }

    public function down(): void
    {
        Schema::table('incapacidades', function (Blueprint $table) {
            $table->dropColumn([
                'estado_revision',
                'observacion_revision',
                'fecha_revision',
            ]);
        });
    }
};
