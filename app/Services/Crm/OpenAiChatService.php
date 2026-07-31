<?php

namespace App\Services\Crm;

use App\Models\Crm\ChatbotConfiguracion;
use App\Models\Crm\ChatbotConversacion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiChatService
{
    private const HERRAMIENTAS = [
        [
            'type' => 'function',
            'function' => [
                'name' => 'guardar_datos_visitante',
                'description' => 'Guarda los datos de contacto que el visitante comparta naturalmente durante la conversación. Úsala cada vez que mencione uno o varios datos, aunque todavía falten los demás.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'nombre' => ['type' => 'string', 'description' => 'Nombre del visitante'],
                        'email' => [
                            'type' => 'string',
                            'format' => 'email',
                            'description' => 'Correo electrónico válido del visitante, con usuario, símbolo @ y dominio',
                        ],
                        'empresa' => ['type' => 'string', 'description' => 'Empresa del visitante'],
                        'telefono' => ['type' => 'string', 'description' => 'Teléfono del visitante'],
                        'nit' => ['type' => 'string', 'description' => 'NIT o número de identificación del visitante o empresa'],
                        'direccion' => ['type' => 'string', 'description' => 'Dirección de entrega o facturación'],
                    ],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'identificar_cliente_cotizacion',
                'description' => 'Busca al visitante actual en el CRM por los datos ya guardados. Si no existe y están todos los datos obligatorios, lo registra. Úsala antes de cotizar.',
                'parameters' => ['type' => 'object'],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'consultar_referencias_cotizacion',
                'description' => 'Busca precios y especificaciones en cotizaciones históricas reales. Debes usarla antes de ofrecer un valor; nunca inventes precios.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'descripcion' => ['type' => 'string'],
                        'ancho_cm' => ['type' => 'number'],
                        'largo_cm' => ['type' => 'number'],
                        'calibre' => ['type' => 'number'],
                        'cantidad' => ['type' => 'number', 'description' => 'Cantidad de paquetes o unidades solicitada'],
                    ],
                    'required' => ['descripcion', 'cantidad'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'crear_cotizacion',
                'description' => 'Crea la cotización real después de identificar al cliente, consultar referencias y recibir confirmación expresa del visitante sobre el resumen y valor.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'items' => [
                            'type' => 'array', 'minItems' => 1,
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'referencia_detalle_id' => ['type' => 'integer'],
                                    'descripcion' => ['type' => 'string'],
                                    'ancho_cm' => ['type' => 'number'],
                                    'largo_cm' => ['type' => 'number'],
                                    'calibre' => ['type' => 'number'],
                                    'cantidad' => ['type' => 'number'],
                                    'cliente_clb' => ['type' => 'string'],
                                    'cantidad_requerida_kg' => ['type' => 'number'],
                                ],
                                'required' => ['referencia_detalle_id', 'descripcion', 'cantidad'],
                            ],
                        ],
                    ],
                    'required' => ['items'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'escalar_a_humano',
                'description' => 'Transfiere la conversación a un asesor humano cuando el visitante lo pide explícitamente, o cuando no puedes resolver su solicitud.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'motivo' => ['type' => 'string', 'description' => 'Motivo breve de la escalación'],
                    ],
                    'required' => ['motivo'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'agendar_cita',
                'description' => 'Agenda una cita o llamada con el visitante cuando ya confirmó la fecha y hora deseadas.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'fecha_hora' => ['type' => 'string', 'description' => 'Fecha y hora en formato "YYYY-MM-DD HH:MM", zona horaria America/Bogota'],
                        'titulo' => ['type' => 'string', 'description' => 'Título breve de la cita'],
                        'notas' => ['type' => 'string', 'description' => 'Notas adicionales relevantes para el asesor'],
                    ],
                    'required' => ['fecha_hora'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'consultar_productos',
                'description' => 'Busca en el catálogo de productos de la empresa, opcionalmente filtrando por categoría (ej. "aseo") y/o un término de búsqueda. Úsala cuando el visitante pregunte qué productos tienen, precios en general, o disponibilidad de algo.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'categoria' => ['type' => 'string', 'description' => 'Nombre (o parte del nombre) de la categoría de productos, ej. "aseo"'],
                        'busqueda' => ['type' => 'string', 'description' => 'Palabra clave para buscar en el nombre o descripción del producto'],
                    ],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'listar_categorias',
                'description' => 'Devuelve la lista de categorías de productos disponibles. Úsala si el visitante pregunta qué tipos de productos manejan.',
                'parameters' => ['type' => 'object'],
            ],
        ],
    ];

    /**
     * Llama a la API de OpenAI con el system prompt configurado + el historial de la conversación.
     * $historial debe venir en formato messages[] de Chat Completions (role/content, o role/tool_calls).
     */
    public function responder(ChatbotConfiguracion $config, ChatbotConversacion $conversacion, array $historial): array
    {
        $mensajes = array_merge(
            [['role' => 'system', 'content' => $this->construirPrompt($config, $conversacion)]],
            $historial
        );

        try {
            $modelo = $config->openai_model ?: config('services.openai.model');
            $payload = [
                'model' => $modelo,
                'messages' => $mensajes,
                'tools' => self::HERRAMIENTAS,
                'temperature' => 0.4,
            ];

            // Los modelos de razonamiento recientes requieren desactivarlo
            // para usar function tools mediante Chat Completions. Modelos
            // como gpt-4o-mini no reconocen este argumento.
            if ($modelo === 'gpt-5.6-luna') {
                $payload['reasoning_effort'] = 'none';
            }

            $response = Http::withToken(config('services.openai.key'))
                ->timeout(20)
                ->post('https://api.openai.com/v1/chat/completions', $payload);

            if (!$response->successful()) {
                Log::error('OpenAI respondió con error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $this->respuestaFallback();
            }

            $mensaje = $response->json('choices.0.message');

            if (!empty($mensaje['tool_calls'])) {
                return [
                    'tipo' => 'tool_calls',
                    'tool_calls' => $mensaje['tool_calls'],
                    'contenido' => $mensaje['content'] ?? null,
                ];
            }

            return [
                'tipo' => 'texto',
                'contenido' => $mensaje['content'] ?? 'Disculpa, no logré procesar tu mensaje. ¿Puedes reformularlo?',
            ];
        } catch (\Throwable $e) {
            Log::error('Error llamando a OpenAI', ['error' => $e->getMessage()]);

            return $this->respuestaFallback();
        }
    }

    private function construirPrompt(ChatbotConfiguracion $config, ChatbotConversacion $conversacion): string
    {
        $datosLead = trim(implode(', ', array_filter([
            $conversacion->nombre_lead ? "Nombre: {$conversacion->nombre_lead}" : null,
            $conversacion->email_lead ? "Correo: {$conversacion->email_lead}" : null,
            $conversacion->empresa_lead ? "Empresa: {$conversacion->empresa_lead}" : null,
            $conversacion->telefono_lead ? "Teléfono: {$conversacion->telefono_lead}" : null,
            $conversacion->nit_lead ? "NIT/identificación: {$conversacion->nit_lead}" : null,
            $conversacion->direccion_lead ? "Dirección: {$conversacion->direccion_lead}" : null,
        ])));

        $contexto = $datosLead ? "\n\nDatos del visitante ya capturados: {$datosLead}." : '';
        $fechaActual = now()->timezone('America/Bogota')->format('Y-m-d H:i');

        $contextoSitio = $config->sitio_web_contexto
            ? "\n\nInformación pública extraída del sitio web de la empresa:\n{$config->sitio_web_contexto}\n"
            : '';
        $contextoComercial = $config->contexto_comercial
            ? "\n\nVisión y contexto comercial validado por la empresa (fuente prioritaria):\n{$config->contexto_comercial}\n"
            : '';

        return $config->prompt_sistema . $contextoComercial . $contextoSitio . $contexto
            . "\n\nLa conversación empieza directamente, sin formulario. Atiende primero la necesidad del visitante y solicita sus datos de contacto de forma natural, uno a la vez y solo cuando sea pertinente. "
            . 'Cuando comparta nombre, correo, empresa o teléfono, usa guardar_datos_visitante inmediatamente. No afirmes que guardaste datos que no haya proporcionado.'
            . "\n\nReglas de continuidad: interpreta respuestas breves como \"sí\", \"no\", \"bolsas\" o una medida usando la pregunta inmediatamente anterior. "
            . 'Nunca repitas una pregunta que el visitante ya respondió. Si menciona un producto o categoría, consulta el catálogo inmediatamente con consultar_productos; no vuelvas a preguntarle si busca un producto. '
            . 'Después de una confirmación, avanza a la siguiente pregunta útil y distinta. '
            . 'Responde con calidez: reconoce primero lo que dijo el visitante con una frase breve como "¡Claro!", "Con gusto" o "Perfecto", aporta información útil y haz como máximo una pregunta por mensaje. Evita respuestas secas o que parezcan un interrogatorio.'
            . "\n\nFlujo obligatorio para cotizaciones: atiende tú mismo la solicitud. Guarda los datos del visitante y usa identificar_cliente_cotizacion. Recopila descripción, cantidad y, para bolsas, ancho en cm, largo en cm y calibre, haciendo máximo una pregunta por mensaje. Usa consultar_referencias_cotizacion para obtener precios históricos reales. Si no hay referencia segura, pide la especificación faltante o propone atención humana; jamás inventes un precio. Presenta al visitante un resumen con producto, cantidad, especificaciones, subtotal/IVA/total disponible y pregunta si confirma. Solo después de una respuesta afirmativa usa crear_cotizacion. La cotización quedará pendiente de aprobación; no prometas que está aprobada ni enviada. No muestres IDs internos ni menciones cotizaciones de otros clientes."
            . ' Para otras solicitudes que requieran seguimiento o atención personalizada, pregunta amablemente si desea que lo atienda un asesor humano. Solo usa escalar_a_humano cuando el visitante confirme que sí.'
            . ' Antes de confirmar que guardaste un correo, debes usar guardar_datos_visitante. Si la herramienta indica que el correo es inválido, informa amablemente el error y solicítalo nuevamente; nunca continúes una cotización dando por válido un correo rechazado.'
            . "\n\nFecha y hora actual: {$fechaActual} (America/Bogota). Úsala como referencia para agendar citas.";
    }

    private function respuestaFallback(): array
    {
        return [
            'tipo' => 'texto',
            'contenido' => 'Tuve un problema temporal para responder. Por favor, intenta enviar tu mensaje nuevamente.',
            // Un fallo de OpenAI no debe dejar la conversación bloqueada en
            // espera de un humano. La escalación solo ocurre cuando el bot o
            // el visitante la solicitan explícitamente.
            'forzar_escalar' => false,
        ];
    }
}
