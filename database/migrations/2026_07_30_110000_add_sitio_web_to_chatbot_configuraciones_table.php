<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_configuraciones', function (Blueprint $table) {
            $table->string('sitio_web_url')->nullable()->after('prompt_sistema');
            $table->text('sitio_web_contexto')->nullable()->after('sitio_web_url');
            $table->timestamp('sitio_web_actualizado_at')->nullable()->after('sitio_web_contexto');
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_configuraciones', function (Blueprint $table) {
            $table->dropColumn([
                'sitio_web_url',
                'sitio_web_contexto',
                'sitio_web_actualizado_at',
            ]);
        });
    }
};
