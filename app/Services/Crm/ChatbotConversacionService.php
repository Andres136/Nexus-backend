<?php

namespace App\Services\Crm;

use App\Models\Crm\ChatbotConfiguracion;
use App\Models\Crm\ChatbotConversacion;
use App\Models\Crm\ChatbotMensaje;
use App\Models\Departamentos;
use App\Models\User;
use App\Notifications\Crm\ChatbotConversacionAsignadaNotificacion;
use App\Notifications\Crm\ChatbotEscaladoNotificacion;
use App\RolEnum;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class ChatbotConversacionService
{
    public function __construct(
        private readonly OpenAiChatService $openAi,
        private readonly ChatbotCitaService $citaService,
        private readonly ChatbotCatalogoService $catalogoService,
        private readonly ChatbotCotizacionService $cotizacionService
    ) {
    }

    public function configuracionActiva(): ChatbotConfiguracion
    {
        return ChatbotConfiguracion::query()->firstOrCreate([], [
            'nombre' => 'Asistente virtual',
            'mensaje_bienvenida' => '¡Hola! ¿En qué te puedo ayudar hoy?',
            'prompt_sistema' => 'Eres un asistente comercial virtual. Responde en español, de forma breve, clara y amable. '
                . 'Si el visitante pregunta por productos, precios en general o categorías (ej. productos de aseo), usa la función consultar_productos o listar_categorias antes de responder. '
                . 'Si el visitante pide hablar con una persona, o si no puedes resolver su solicitud, usa la función escalar_a_humano. '
                . 'Si el visitante quiere agendar una cita o llamada y ya confirmó fecha y hora, usa la función agendar_cita.',
            'activo' => true,
            'openai_model' => config('services.openai.model', 'gpt-4o-mini'),
        ]);
    }

    public function crear(array $datos): ChatbotConversacion
    {
        $conversacion = ChatbotConversacion::create([
            'token' => (string) Str::uuid(),
            'estado' => 'bot',
            'origen_url' => $datos['origen_url'] ?? null,
            'dominio' => $datos['dominio'] ?? null,
            'ip' => $datos['ip'] ?? null,
            'ultima_actividad_at' => now(),
        ]);

        $config = $this->configuracionActiva();
        $this->registrarMensaje($conversacion, 'bot', null, $config->mensaje_bienvenida);

        return $conversacion;
    }

    public function capturarLead(ChatbotConversacion $conversacion, array $datos): ChatbotConversacion
    {
        $conversacion->update([
            'nombre_lead' => $datos['nombre_lead'],
            'email_lead' => $datos['email_lead'],
            'empresa_lead' => $datos['empresa_lead'] ?? null,
            'telefono_lead' => $datos['telefono_lead'],
        ]);

        return $conversacion->fresh();
    }

    public function procesarMensajeLead(ChatbotConversacion $conversacion, string $contenido): void
    {
        $this->registrarMensaje($conversacion, 'lead', null, $contenido);
        $conversacion->update(['ultima_actividad_at' => now()]);

        if ($conversacion->fresh()->estado !== 'bot') {
            // Ya hay un humano en la conversación; el bot no interviene.
            return;
        }

        if (!$conversacion->email_lead && $this->ultimaPreguntaSolicitaCorreo($conversacion)) {
            $email = $this->extraerEmailValido($contenido);

            if (!$email) {
                $this->registrarMensaje(
                    $conversacion,
                    'bot',
                    null,
                    'El correo no parece válido. Por favor, escríbelo nuevamente con este formato: nombre@dominio.com.'
                );

                return;
            }

            $conversacion->update(['email_lead' => $email]);
        }

        $this->generarRespuestaBot($conversacion);
    }

    private function ultimaPreguntaSolicitaCorreo(ChatbotConversacion $conversacion): bool
    {
        // Se excluye el mensaje del visitante que acabamos de registrar.
        $ultimoMensajeBot = $conversacion->mensajes()
            ->where('remitente', 'bot')
            ->reorder('id', 'desc')
            ->value('contenido');

        if (!$ultimoMensajeBot) {
            return false;
        }

        $texto = Str::lower($ultimoMensajeBot);

        return str_contains($texto, 'correo')
            || str_contains($texto, 'e-mail')
            || str_contains($texto, 'email');
    }

    private function extraerEmailValido(string $contenido): ?string
    {
        if (!preg_match('/[a-z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+/iu', $contenido, $coincidencias)) {
            return null;
        }

        $email = trim($coincidencias[0]);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function generarRespuestaBot(ChatbotConversacion $conversacion): void
    {
        $config = $this->configuracionActiva();
        $historial = $this->historialParaIa($conversacion);

        $resultado = $this->openAi->responder($config, $conversacion, $historial);

        if (($resultado['tipo'] ?? null) === 'texto'
            && $this->repiteUltimaRespuestaBot($conversacion, $resultado['contenido'] ?? '')) {
            $historial[] = [
                'role' => 'system',
                'content' => 'La respuesta propuesta repite la última pregunta del asistente. No la repitas. Interpreta la respuesta más reciente del visitante en contexto, usa las herramientas si corresponde y avanza al siguiente paso útil.',
            ];
            $resultado = $this->openAi->responder($config, $conversacion, $historial);
        }

        if (($resultado['tipo'] ?? null) === 'tool_calls') {
            $this->ejecutarToolCallsYResponder($conversacion, $historial, $resultado);

            return;
        }

        $this->registrarMensaje($conversacion, 'bot', null, $resultado['contenido']);

        if (($resultado['forzar_escalar'] ?? false) === true) {
            $this->escalarAHumano($conversacion, 'Error técnico del asistente');
        }
    }

    private function repiteUltimaRespuestaBot(ChatbotConversacion $conversacion, string $respuesta): bool
    {
        $ultimaRespuesta = $conversacion->mensajes()
            ->where('remitente', 'bot')
            ->reorder('id', 'desc')
            ->value('contenido');

        if (!$ultimaRespuesta || trim($respuesta) === '') {
            return false;
        }

        $normalizar = static fn (string $texto): string => preg_replace(
            '/[^\pL\pN]+/u',
            ' ',
            Str::lower(trim($texto))
        ) ?? '';

        return trim($normalizar($ultimaRespuesta)) === trim($normalizar($respuesta));
    }

    private function ejecutarToolCallsYResponder(ChatbotConversacion $conversacion, array $historialPrevio, array $resultado): void
    {
        $ejecutados = [];
        $mensajesTool = [];
        $resultadoActual = $resultado;

        for ($ronda = 0; $ronda < 5; $ronda++) {
            $mensajesTool[] = [
                'role' => 'assistant',
                'content' => $resultadoActual['contenido'] ?? null,
                'tool_calls' => $resultadoActual['tool_calls'],
            ];

            foreach ($resultadoActual['tool_calls'] as $toolCall) {
                $nombre = $toolCall['function']['name'] ?? null;
                $args = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?: [];

                $resultadoTexto = match ($nombre) {
                    'guardar_datos_visitante' => $this->guardarDatosVisitante($conversacion, $args),
                    'identificar_cliente_cotizacion' => $this->cotizacionService->identificarCliente($conversacion),
                    'consultar_referencias_cotizacion' => $this->cotizacionService->consultarReferencias($args),
                    'crear_cotizacion' => $this->cotizacionService->crear($conversacion, $args),
                    'escalar_a_humano' => $this->escalarAHumano($conversacion, $args['motivo'] ?? 'Solicitado por el visitante'),
                    'agendar_cita' => $this->agendarCitaDesdeBot($conversacion, $args),
                    'consultar_productos' => $this->catalogoService->consultarProductos($args['categoria'] ?? null, $args['busqueda'] ?? null),
                    'listar_categorias' => $this->catalogoService->listarCategorias(),
                    default => 'Acción no reconocida.',
                };

                $mensajesTool[] = ['role' => 'tool', 'tool_call_id' => $toolCall['id'] ?? '', 'content' => $resultadoTexto];
                $ejecutados[] = ['tool' => $nombre, 'args' => $args, 'resultado' => $resultadoTexto];
            }

            $conversacion->refresh();
            if ($conversacion->estado !== 'bot') {
                $this->registrarMensaje($conversacion, 'bot', null, 'Listo, en un momento te atiende un asesor. 👋', ['tool_calls' => $ejecutados]);
                return;
            }

            $resultadoActual = $this->openAi->responder(
                $this->configuracionActiva(),
                $conversacion,
                array_merge($historialPrevio, $mensajesTool)
            );

            if (($resultadoActual['tipo'] ?? null) !== 'tool_calls') {
                $this->registrarMensaje($conversacion, 'bot', null, $resultadoActual['contenido'] ?? 'Listo.', ['tool_calls' => $ejecutados]);
                return;
            }
        }

        $this->registrarMensaje($conversacion, 'bot', null, 'Necesito confirmar algunos datos antes de continuar. ¿Puedes intentarlo nuevamente?', ['tool_calls' => $ejecutados]);
    }

    public function escalarAHumano(ChatbotConversacion $conversacion, string $motivo): string
    {
        if ($conversacion->estado === 'bot') {
            $conversacion->update(['estado' => 'esperando_humano']);
            $this->notificarEscalado($conversacion, $motivo);
        }

        return 'Conversación transferida a un asesor humano.';
    }

    public function agendarCitaDesdeBot(ChatbotConversacion $conversacion, array $args): string
    {
        $fechaHora = $args['fecha_hora'] ?? null;

        if (!$fechaHora) {
            return 'No se pudo agendar: falta la fecha y hora.';
        }

        try {
            $fechaInicio = Carbon::parse($fechaHora, 'America/Bogota');
        } catch (\Throwable) {
            return 'La fecha indicada no es válida.';
        }

        $cita = $this->citaService->crearDesdeBot($conversacion, $fechaInicio, $args['titulo'] ?? null, $args['notas'] ?? null);

        return "Cita agendada para el {$cita->fecha_inicio->format('d/m/Y H:i')}.";
    }

    public function registrarMensaje(
        ChatbotConversacion $conversacion,
        string $remitente,
        ?int $userId,
        string $contenido,
        ?array $metadata = null
    ): ChatbotMensaje {
        return $conversacion->mensajes()->create([
            'remitente' => $remitente,
            'user_id' => $userId,
            'contenido' => $contenido,
            'metadata' => $metadata,
        ]);
    }

    public function responderComoAgente(ChatbotConversacion $conversacion, User $agente, string $contenido): ChatbotMensaje
    {
        $conversacion->update(['ultima_actividad_at' => now()]);

        return $this->registrarMensaje($conversacion, 'agente', $agente->id, $contenido);
    }

    public function asignar(ChatbotConversacion $conversacion, int $ejecutivoId, User $asignadoPor): ChatbotConversacion
    {
        $conversacion->update([
            'user_id' => $ejecutivoId,
            'asignado_por' => $asignadoPor->id,
            'asignado_at' => now(),
            'estado' => 'asignada',
        ]);

        $ejecutivo = User::find($ejecutivoId);
        if ($ejecutivo) {
            $ejecutivo->notify(new ChatbotConversacionAsignadaNotificacion($conversacion));
        }

        return $conversacion->fresh(['usuario']);
    }

    public function asignarAIa(ChatbotConversacion $conversacion): ChatbotConversacion
    {
        abort_if($conversacion->estado === 'cerrada', 422, 'No se puede asignar la IA a una conversación cerrada.');

        $conversacion->update([
            'estado' => 'bot',
            'user_id' => null,
            'asignado_por' => null,
            'asignado_at' => null,
            'ultima_actividad_at' => now(),
        ]);

        $ultimoMensaje = $conversacion->mensajes()->reorder('id', 'desc')->first();
        if ($ultimoMensaje?->remitente === 'lead') {
            $this->generarRespuestaBot($conversacion);
        }

        return $conversacion->fresh(['usuario']);
    }

    public function cerrar(ChatbotConversacion $conversacion): ChatbotConversacion
    {
        $conversacion->update(['estado' => 'cerrada', 'cerrada_at' => now()]);

        return $conversacion->fresh();
    }

    public function listar(User $user, array $filtros): LengthAwarePaginator
    {
        $esPrivilegiado = $user->role_id == RolEnum::ADMINISTRADOR->value || $user->esResponsableDeSuDepartamento();

        return ChatbotConversacion::query()
            ->with('usuario:id,name,apellidos,foto_perfil')
            ->withCount('mensajes')
            ->when(!$esPrivilegiado, fn ($q) => $q->where('user_id', $user->id))
            ->when($filtros['estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado))
            ->when($filtros['sin_asignar'] ?? false, fn ($q) => $q->whereNull('user_id'))
            ->orderByDesc('ultima_actividad_at')
            ->paginate(20);
    }

    public function detalle(int $id): ChatbotConversacion
    {
        return ChatbotConversacion::with(['mensajes.usuario:id,name', 'usuario:id,name,apellidos,foto_perfil'])->findOrFail($id);
    }

    public function estadoPublico(ChatbotConversacion $conversacion, ?int $desdeId): array
    {
        $mensajes = $conversacion->mensajes()
            ->when($desdeId, fn ($q) => $q->where('id', '>', $desdeId))
            ->orderBy('id')
            ->get();

        $agente = null;
        if ($conversacion->user_id && $conversacion->estado === 'asignada') {
            // Se consulta el usuario completo para incluir apellidos y su foto
            // de perfil. `users.imagen` se reserva para su uso original.
            $usuario = User::find($conversacion->user_id);
            if ($usuario) {
                $agente = [
                    'nombre' => $usuario->nombre_completo ?: $usuario->name,
                    'imagen_url' => $usuario->fotoPerfilUrlCompleta(),
                ];
            }
        }

        return [
            'estado' => $conversacion->estado,
            'agente' => $agente,
            'mensajes' => $mensajes->map(fn (ChatbotMensaje $m) => [
                'id' => $m->id,
                'remitente' => $m->remitente,
                'contenido' => $m->contenido,
                'created_at' => $m->created_at,
            ])->values(),
        ];
    }

    private function historialParaIa(ChatbotConversacion $conversacion): array
    {
        return $conversacion->mensajes()
            ->reorder('id', 'desc')
            ->limit(20)
            ->get()
            ->reverse()
            ->map(fn (ChatbotMensaje $m) => [
                'role' => $m->remitente === 'lead' ? 'user' : 'assistant',
                'content' => $m->contenido,
            ])
            ->values()
            ->all();
    }

    private function notificarEscalado(ChatbotConversacion $conversacion, string $motivo): void
    {
        $config = $this->configuracionActiva();
        $destinatarios = collect();

        if ($config->departamento_id) {
            $departamento = Departamentos::find($config->departamento_id);
            if ($departamento?->responsable) {
                $destinatarios->push($departamento->responsable);
            }
        }

        if ($destinatarios->isEmpty()) {
            $destinatarios = User::where('role_id', RolEnum::ADMINISTRADOR->value)->get();
        }

        if ($destinatarios->isNotEmpty()) {
            Notification::send($destinatarios, new ChatbotEscaladoNotificacion($conversacion, $motivo));
        }
    }

    private function guardarDatosVisitante(ChatbotConversacion $conversacion, array $datos): string
    {
        $correoInvalido = false;
        if (array_key_exists('email', $datos)) {
            $email = is_string($datos['email']) ? trim($datos['email']) : '';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                unset($datos['email']);
                $correoInvalido = true;
            } else {
                $datos['email'] = $email;
            }
        }

        $campos = array_filter([
            'nombre_lead' => $datos['nombre'] ?? null,
            'email_lead' => $datos['email'] ?? null,
            'empresa_lead' => $datos['empresa'] ?? null,
            'telefono_lead' => $datos['telefono'] ?? null,
            'nit_lead' => $datos['nit'] ?? null,
            'direccion_lead' => $datos['direccion'] ?? null,
        ], static fn ($valor) => is_string($valor) && trim($valor) !== '');

        if ($campos === [] && $correoInvalido) {
            return 'El correo proporcionado no es válido y NO fue guardado. Pide al visitante que lo escriba nuevamente en formato nombre@dominio.com.';
        }

        if ($campos === []) {
            return 'No se recibieron datos nuevos para guardar.';
        }

        $conversacion->update(array_map(
            static fn (string $valor) => trim($valor),
            $campos
        ));

        if ($correoInvalido) {
            return 'Se guardaron los demás datos, pero el correo NO fue guardado porque no es válido. Pide al visitante que lo escriba nuevamente en formato nombre@dominio.com.';
        }

        return 'Datos del visitante guardados y validados correctamente.';
    }
}
