<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alistamiento', function (Blueprint $table) {
            $table->unsignedBigInteger('orden_trabajo_id')->nullable()->change();
            $table->enum('tipo_origen', ['OT', 'LIBRE'])->default('OT')->after('orden_trabajo_id');
            $table->foreignId('sede_id')->nullable()->after('tipo_origen')->constrained('sedes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('alistamiento', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sede_id');
            $table->dropColumn('tipo_origen');
            $table->unsignedBigInteger('orden_trabajo_id')->nullable(false)->change();
        });
    }
};
