<?php

use App\ImpuestoOperacionEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('impuestos', function (Blueprint $table) {
            $table->string('operacion', 10)
                ->default(ImpuestoOperacionEnum::SUMA->value)
                ->after('porcentaje');
        });
    }

    public function down(): void
    {
        Schema::table('impuestos', function (Blueprint $table) {
            $table->dropColumn('operacion');
        });
    }
};
