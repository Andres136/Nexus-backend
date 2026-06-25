<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets_historial', function (Blueprint $table) {
            $table->string('link', 2048)->nullable()->after('soporte');
        });
    }

    public function down(): void
    {
        Schema::table('tickets_historial', function (Blueprint $table) {
            $table->dropColumn('link');
        });
    }
};
