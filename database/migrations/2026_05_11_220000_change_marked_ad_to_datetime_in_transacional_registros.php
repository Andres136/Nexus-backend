<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transacional_registros', function (Blueprint $table) {
            $table->dateTime('marked_ad')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('transacional_registros', function (Blueprint $table) {
            $table->time('marked_ad')->nullable(false)->change();
        });
    }
};
