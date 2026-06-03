<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('horas_extras', function (Blueprint $table) {
            $table->foreignId('sede_id')
                ->nullable()
                ->after('user_id')
                ->constrained('sedes')
                ->nullOnDelete();

            $table->foreignId('kiosko_device_id')
                ->nullable()
                ->after('sede_id')
                ->constrained('kiosko_devices')
                ->nullOnDelete();

            $table->foreignId('solicitado_por')
                ->nullable()
                ->after('kiosko_device_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('origen', ['admin', 'kiosko'])
                ->default('admin')
                ->after('solicitado_por');
        });
    }

    public function down(): void
    {
        Schema::table('horas_extras', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sede_id');
            $table->dropConstrainedForeignId('kiosko_device_id');
            $table->dropConstrainedForeignId('solicitado_por');
            $table->dropColumn('origen');
        });
    }
};
