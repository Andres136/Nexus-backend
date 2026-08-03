<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->timestamp('enviada_cliente_at')->nullable()->after('motivo_rechazo');
            $table->text('envio_cliente_error')->nullable()->after('enviada_cliente_at');
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', fn (Blueprint $table) => $table->dropColumn(['enviada_cliente_at', 'envio_cliente_error']));
    }
};
