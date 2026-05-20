<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contrataciones', function (Blueprint $table) {
            $table->string('tipo_documento', 10)->default('CC')->after('users_id');
            $table->string('numero_documento', 20)->after('tipo_documento');
        });
    }

    public function down(): void
    {
        Schema::table('contrataciones', function (Blueprint $table) {
            $table->dropColumn(['tipo_documento', 'numero_documento']);
        });
    }
};
