<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contrataciones', function (Blueprint $table) {
            $table->renameColumn('caja_pensiones_id', 'caja_compensacion_id');
        });
    }

    public function down(): void
    {
        Schema::table('contrataciones', function (Blueprint $table) {
            $table->renameColumn('caja_compensacion_id', 'caja_pensiones_id');
        });
    }
};
