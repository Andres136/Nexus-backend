<?php

namespace App\Services\Crm;

use App\Mail\EncuestaEnviadaMail;
use App\Models\Crm\Cliente;
use App\Models\Crm\Encuesta;
use App\Models\Crm\EncuestaEnvio;
use App\Models\Crm\EncuestaRespuesta;
use App\Models\User;
use App\RolEnum;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EncuestaService
{
    private array $rolesCreador = [
        RolEnum::ADMINISTRADOR->value,
    ];

    private array $rolesResultados = [
        RolEnum::ADMINISTRADOR->value,
        RolEnum::ADMINISTRATIVO->value,
    ];

    private array $rolesClientesGlobales = [
        RolEnum::ADMINISTRATIVO->value,
    ];

    // ─── CRUD ────────────────────────────────────────────────────────────────

    public function index($user): array
    {
        return Encuesta::with(['preguntas'])
            ->withCount(['envios', 'envios as envios_respondidas_count' => fn ($q) => $q->where('estado', 'respondida')])
            ->latest()
            ->get()
            ->map(fn (Encuesta $encuesta) => $this->marcarPermisos($encuesta, $user))
            ->toArray();
    }

    public function store(array $data, $user): Encuesta
    {
        $this->autorizarCreador($user);

        $encuesta = Encuesta::create([
            'user_id'     => $user->id,
            'titulo'      => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? null,
            'estado'      => $data['estado'] ?? 'activa',
        ]);

        $this->sincronizarPreguntas($encuesta, $data['preguntas']);

        return $encuesta->load('preguntas');
    }

    public function show(int $id, $user): Encuesta
    {
        return Encuesta::with([
            'preguntas',
            'envios.cliente:id,nombre,email',
        ])->findOrFail($id);
    }

    public function update(int $id, array $data, $user): Encuesta
    {
        $this->autorizarCreador($user);
        $encuesta = Encuesta::findOrFail($id);

        $encuesta->update([
            'titulo'      => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? null,
            'estado'      => $data['estado'] ?? $encuesta->estado,
        ]);

        $this->sincronizarPreguntas($encuesta, $data['preguntas']);

        return $encuesta->load('preguntas');
    }

    public function destroy(int $id, $user): void
    {
        $encuesta = Encuesta::findOrFail($id);
        $this->autorizarEliminar($encuesta, $user);
        $encuesta->delete();
    }

    // ─── ÍNDICE GENERAL DE SATISFACCIÓN ──────────────────────────────────────

    public function indiceGeneral($user): array
    {
        if (!in_array($user->role_id, $this->rolesResultados)) {
            abort(403, 'No tienes permiso para ver los resultados');
        }

        $preguntasEscala = \App\Models\Crm\EncuestaPregunta::where('tipo', 'escala')->get();

        $totalRespuestas     = 0;
        $respuestasSatisfecho = 0;
        $encuestasConDatos   = 0;
        $encuestasVistas     = [];

        foreach ($preguntasEscala as $pregunta) {
            $maxEscala = $pregunta->max_escala ?? 5;
            $umbral    = (int) ceil($maxEscala * 0.7);

            $valores = EncuestaRespuesta::where('pregunta_id', $pregunta->id)->pluck('valor');

            if ($valores->isEmpty()) continue;

            $totalRespuestas      += $valores->count();
            $respuestasSatisfecho += $valores->filter(fn ($v) => (int) $v >= $umbral)->count();

            if (!in_array($pregunta->encuesta_id, $encuestasVistas)) {
                $encuestasVistas[] = $pregunta->encuesta_id;
                $encuestasConDatos++;
            }
        }

        $indice = $totalRespuestas > 0
            ? round(($respuestasSatisfecho / $totalRespuestas) * 100, 1)
            : null;

        return [
            'indice_general'      => $indice,
            'total_respuestas'    => $totalRespuestas,
            'respuestas_positivas'=> $respuestasSatisfecho,
            'encuestas_con_datos' => $encuestasConDatos,
        ];
    }

    // ─── CLIENTES PARA ENCUESTA ───────────────────────────────────────────────

    public function clientesParaEncuesta($user): array
    {
        $puedeVerTodosLosClientes = in_array($user->role_id, $this->rolesClientesGlobales, true);

        $clienteIds = Cliente::query()
            ->when(!$puedeVerTodosLosClientes, fn ($q) => $q->where('user_id', $user->id))
            ->whereHas('ordenes')
            ->pluck('id');

        return Cliente::query()
            ->when(!$puedeVerTodosLosClientes, fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'email'])
            ->map(function ($c) use ($clienteIds) {
                $tieneOrdenes = $clienteIds->contains($c->id);
                $tieneEmail   = !empty($c->email);

                $razon = null;
                if (!$tieneOrdenes && !$tieneEmail) {
                    $razon = 'Sin órdenes de compra ni correo electrónico';
                } elseif (!$tieneOrdenes) {
                    $razon = 'Sin órdenes de compra';
                } elseif (!$tieneEmail) {
                    $razon = 'Sin correo electrónico';
                }

                return [
                    'id'         => $c->id,
                    'nombre'     => $c->nombre,
                    'email'      => $c->email,
                    'habilitado' => $tieneOrdenes && $tieneEmail,
                    'razon'      => $razon,
                ];
            })
            ->toArray();
    }

    // ─── ENVÍO ───────────────────────────────────────────────────────────────

    public function enviar(int $encuestaId, array $clienteIdsOriginales, $user): array
    {
        $encuesta = Encuesta::with('preguntas')->findOrFail($encuestaId);
        $puedeEnviarATodosLosClientes = in_array($user->role_id, $this->rolesClientesGlobales, true);

        // Cargar todos los clientes solicitados. Administrativo puede enviar a cualquier cliente.
        $todosClientes = Cliente::whereIn('id', $clienteIdsOriginales)
            ->when(!$puedeEnviarATodosLosClientes, fn ($q) => $q->where('user_id', $user->id))
            ->get()
            ->keyBy('id');

        // IDs que tienen al menos una orden de compra
        $idsConOrdenes = Cliente::whereIn('id', $todosClientes->keys())
            ->whereHas('ordenes')
            ->pluck('id')
            ->flip(); // flip para búsqueda O(1)

        $links    = [];
        $excluidos = [];

        foreach ($todosClientes as $cliente) {
            $tieneOrdenes = $idsConOrdenes->has($cliente->id);
            $tieneEmail   = !empty($cliente->email);

            if (!$tieneOrdenes || !$tieneEmail) {
                $razon = match (true) {
                    !$tieneOrdenes && !$tieneEmail => 'Sin órdenes de compra ni correo electrónico',
                    !$tieneOrdenes                 => 'Sin órdenes de compra',
                    default                        => 'Sin correo electrónico',
                };
                $excluidos[] = [
                    'cliente_id'    => $cliente->id,
                    'cliente_nombre'=> $cliente->nombre,
                    'razon'         => $razon,
                ];
                continue;
            }

            $envio = EncuestaEnvio::firstOrNew([
                'encuesta_id' => $encuestaId,
                'cliente_id'  => $cliente->id,
            ]);

            if (!$envio->exists || $envio->estado === 'respondida') {
                $envio->token        = Str::uuid()->toString();
                $envio->estado       = 'pendiente';
                $envio->responded_at = null;
            }

            $envio->user_id = $user->id;
            $envio->sent_at = now();
            $envio->save();

            $envio->setRelation('encuesta', $encuesta);
            $envio->setRelation('cliente', $cliente);

            $link = config('app.frontend_url') . '/encuesta/' . $envio->token;

            Mail::to($cliente->email)->queue(new EncuestaEnviadaMail($envio, $link));

            $links[] = [
                'cliente_id'    => $cliente->id,
                'cliente_nombre'=> $cliente->nombre,
                'cliente_email' => $cliente->email,
                'token'         => $envio->token,
                'link'          => $link,
                'estado'        => $envio->estado,
            ];
        }

        return [
            'links'     => $links,
            'excluidos' => $excluidos,
        ];
    }

    // ─── RESPUESTA PÚBLICA ────────────────────────────────────────────────────

    public function showPublico(string $token): EncuestaEnvio
    {
        return EncuestaEnvio::with([
            'encuesta.preguntas' => fn ($q) => $q->orderBy('orden'),
            'cliente:id,nombre',
        ])
        ->where('token', $token)
        ->firstOrFail();
    }

    public function responderPublico(string $token, array $respuestas): void
    {
        $envio = EncuestaEnvio::where('token', $token)
            ->where('estado', 'pendiente')
            ->firstOrFail();

        foreach ($respuestas as $r) {
            EncuestaRespuesta::create([
                'envio_id'    => $envio->id,
                'pregunta_id' => $r['pregunta_id'],
                'valor'       => $r['valor'],
            ]);
        }

        $envio->update([
            'estado'       => 'respondida',
            'responded_at' => now(),
        ]);
    }

    // ─── RESULTADOS ───────────────────────────────────────────────────────────

    public function resultados(int $encuestaId, $user, ?int $filtroUserId = null): array
    {
        if (!in_array($user->role_id, $this->rolesResultados)) {
            abort(403, 'No tienes permiso para ver los resultados');
        }

        $encuesta = Encuesta::with('preguntas')->findOrFail($encuestaId);

        $baseEnvios = EncuestaEnvio::where('encuesta_id', $encuestaId)
            ->when($filtroUserId, fn ($q) => $q->where('user_id', $filtroUserId));

        $totalEnvios      = (clone $baseEnvios)->count();
        $totalRespondidas = (clone $baseEnvios)->where('estado', 'respondida')->count();

        $envioIds = (clone $baseEnvios)->pluck('id');

        $resultadosPorPregunta = $encuesta->preguntas->map(function ($pregunta) use ($envioIds) {
            $respuestas = EncuestaRespuesta::whereIn('envio_id', $envioIds)
                ->where('pregunta_id', $pregunta->id)
                ->pluck('valor');

            $maxEscala = $pregunta->max_escala ?? 5;

            $datos = match ($pregunta->tipo) {
                'escala' => [
                    'promedio'      => round($respuestas->avg(), 2),
                    'max_escala'    => $maxEscala,
                    'distribucion'  => collect(range(1, $maxEscala))->mapWithKeys(fn ($n) => [
                        $n => $respuestas->filter(fn ($v) => (int) $v === $n)->count(),
                    ]),
                ],
                'opcion_multiple' => [
                    'conteo' => collect($pregunta->opciones)->mapWithKeys(fn ($op) => [
                        $op => $respuestas->filter(fn ($v) => $v === $op)->count(),
                    ]),
                ],
                default => ['respuestas' => $respuestas->values()],
            };

            return [
                'pregunta_id' => $pregunta->id,
                'texto'       => $pregunta->texto,
                'tipo'        => $pregunta->tipo,
                'total'       => $respuestas->count(),
                'datos'       => $datos,
            ];
        });

        // Índice de satisfacción: % de respuestas en el tramo superior (>= ceil(max/2)+1) de escala
        $preguntasEscala     = $encuesta->preguntas->where('tipo', 'escala');
        $indicesSatisfaccion = null;

        if ($preguntasEscala->isNotEmpty()) {
            $totalEscala     = 0;
            $positivasEscala = 0;

            foreach ($preguntasEscala as $pregunta) {
                $maxEscala = $pregunta->max_escala ?? 5;
                $umbral    = (int) ceil($maxEscala * 0.7); // 70% del máximo = "satisfecho"

                $vals = EncuestaRespuesta::whereIn('envio_id', $envioIds)
                    ->where('pregunta_id', $pregunta->id)
                    ->pluck('valor');

                $totalEscala     += $vals->count();
                $positivasEscala += $vals->filter(fn ($v) => (int) $v >= $umbral)->count();
            }

            $indicesSatisfaccion = $totalEscala > 0
                ? round(($positivasEscala / $totalEscala) * 100, 1)
                : null;
        }

        // Usuarios que han enviado esta encuesta (para el filtro)
        $usuariosRemitentes = EncuestaEnvio::where('encuesta_id', $encuestaId)
            ->distinct('user_id')
            ->pluck('user_id')
            ->filter()
            ->pipe(fn ($ids) => User::whereIn('id', $ids)->get(['id', 'name']))
            ->values()
            ->toArray();

        // Clientes pendientes (enviados pero sin responder)
        $clientesPendientes = (clone $baseEnvios)
            ->where('estado', 'pendiente')
            ->with('cliente:id,nombre,email')
            ->get()
            ->map(fn ($e) => [
                'cliente_id'    => $e->cliente_id,
                'nombre'        => $e->cliente?->nombre,
                'email'         => $e->cliente?->email,
                'enviado_el'    => $e->sent_at?->format('Y-m-d H:i'),
            ])
            ->values()
            ->toArray();

        return [
            'encuesta_id'         => $encuestaId,
            'titulo'              => $encuesta->titulo,
            'total_envios'        => $totalEnvios,
            'total_respondidas'   => $totalRespondidas,
            'tasa_respuesta'      => $totalEnvios > 0 ? round(($totalRespondidas / $totalEnvios) * 100, 2) : 0,
            'indice_satisfaccion' => $indicesSatisfaccion,
            'usuarios_remitentes' => $usuariosRemitentes,
            'clientes_pendientes' => $clientesPendientes,
            'preguntas'           => $resultadosPorPregunta,
        ];
    }

    // ─── HELPERS ─────────────────────────────────────────────────────────────

    private function sincronizarPreguntas(Encuesta $encuesta, array $preguntas): void
    {
        $encuesta->preguntas()->delete();

        foreach ($preguntas as $index => $p) {
            $encuesta->preguntas()->create([
                'texto'      => $p['texto'],
                'tipo'       => $p['tipo'],
                'opciones'   => $p['opciones'] ?? null,
                'orden'      => $p['orden'] ?? $index,
                'requerida'  => $p['requerida'] ?? true,
                'max_escala' => ($p['tipo'] === 'escala') ? ($p['max_escala'] ?? 5) : null,
            ]);
        }
    }

    private function autorizarCreador($user): void
    {
        if (!in_array($user->role_id, $this->rolesCreador)) {
            abort(403, 'No tienes permiso para realizar esta acción');
        }
    }

    private function autorizarEliminar(Encuesta $encuesta, $user): void
    {
        if ($this->fechaYaPaso($encuesta) && !$this->esAdministrador($user)) {
            abort(403, 'Solo el Administrador puede eliminar encuestas de fechas pasadas.');
        }

        if (!$this->esAdministrador($user)) {
            abort(403, 'Solo el Administrador puede eliminar encuestas.');
        }
    }

    private function marcarPermisos(Encuesta $encuesta, $user): Encuesta
    {
        $encuesta->setAttribute('fecha_pasada', $this->fechaYaPaso($encuesta));
        $encuesta->setAttribute('puede_eliminar', $this->esAdministrador($user));

        return $encuesta;
    }

    private function fechaYaPaso(Encuesta $encuesta): bool
    {
        return $encuesta->created_at?->lt(today()) ?? false;
    }

    private function esAdministrador($user): bool
    {
        return (int) $user->role_id === RolEnum::ADMINISTRADOR->value;
    }
}
