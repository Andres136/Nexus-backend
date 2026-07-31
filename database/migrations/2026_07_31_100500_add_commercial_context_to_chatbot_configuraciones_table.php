<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_configuraciones', function (Blueprint $table) {
            $table->string('sitio_web_productos_url', 500)->nullable()->after('sitio_web_url');
            $table->text('contexto_comercial')->nullable()->after('sitio_web_contexto');
        });

        DB::table('chatbot_configuraciones')->whereNull('contexto_comercial')->update([
            'contexto_comercial' => 'Setasplast ofrece soluciones innovadoras en productos plásticos de alta calidad para ayudar a empresas a proteger, empacar, presentar y entregar mejor sus productos. Su portafolio incluye bolsas y empaques, línea fabricada con material 100 % recuperado, línea sostenible, soluciones personalizadas y línea para mascotas. La conversación comercial debe partir de la necesidad concreta del cliente y mostrar cómo una solución adecuada de empaque puede aportar presentación, organización, protección y eficiencia. Solo deben comunicarse certificaciones, características ambientales o beneficios técnicos que estén respaldados por el sitio web o el catálogo vigente.',
        ]);
    }

    public function down(): void
    {
        Schema::table('chatbot_configuraciones', fn (Blueprint $table) => $table->dropColumn(['sitio_web_productos_url', 'contexto_comercial']));
    }
};
