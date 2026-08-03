<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $contexto = 'Setasplast ofrece soluciones innovadoras en productos plásticos de alta calidad para ayudar a empresas a proteger, empacar, presentar y entregar mejor sus productos. Su portafolio incluye bolsas y empaques, línea fabricada con material 100 % recuperado, línea sostenible, soluciones personalizadas y línea para mascotas. Ofrece bolsas biodegradables para uso comercial y bolsas compostables; estas características solo deben mencionarse cuando correspondan al producto concreto y a sus condiciones de disposición. La empresa comunica las certificaciones ISO 9001:2015 en gestión de calidad, ISO 14001:2015 en gestión ambiental e ISO 45001:2018 en seguridad y salud en el trabajo. Entre sus acciones ambientales publicadas están la medición, reducción y compensación de la huella de carbono, la siembra de 1.000 árboles en Cundinamarca, procesos de producción sostenible, eficiencia energética y gestión responsable de residuos. Su enfoque de economía circular promueve compartir, reutilizar, reparar, renovar y reciclar materiales y productos existentes. La conversación comercial debe partir de la necesidad concreta del cliente y mostrar cómo una solución adecuada de empaque puede aportar presentación, organización, protección, eficiencia y menor impacto ambiental. Solo deben comunicarse afirmaciones respaldadas por este contexto o por el catálogo vigente.';

        DB::table('chatbot_configuraciones')
            ->where(function ($query) {
                $query->whereNull('contexto_comercial')
                    ->orWhere('contexto_comercial', 'not like', '%ISO 14001:2015%');
            })
            ->update(['contexto_comercial' => $contexto]);
    }

    public function down(): void
    {
        // El contexto puede haber sido editado por el usuario; no se revierte para no perder esos cambios.
    }
};
