<?php

namespace App\Services\Crm;

use App\Mail\EncuestaEnviadaMail;
use App\Models\Crm\Encuesta;
use App\Models\Crm\EncuestaEnvio;
use App\Models\Crm\EncuestaRespuesta;
use App\RolEnum;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EncuestaService
{
    private array $rolesAdmin = [
        RolEnum::ADMINISTRADOR->value,
        RolEnum::ADMINISTRATIVO->value,
        RolEnum::COMERCIAL->value,
    ];

    private array $rolesCreador = [
        RolEnum::ADMINISTRADOR->value,
    ];

    // ─── CRUD ────────────────────────────────────────────────────────────────

    public function index($user): array
    {
        return Encuesta::with(['preguntas'])
            ->withCount(['envios', 'envios as envios_respondidas_count' => fn ($q) => $q->where('estado', 'respondida')])
            ->latest()
            ->get()
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
        $this->autorizarCreador($user);
        Encuesta::findOrFail($id)->delete();
    }

    // ─── ENVÍO ───────────────────────────────────────────────────────────────

    public function enviar(int $encuestaId, array $clienteIds, $user): array
    {
        $encuesta = Encuesta::with('preguntas')->findOrFail($encuestaId);

        // Comercial solo puede enviar a sus clientes
        if (!in_array($user->role_id, $this->rolesAdmin)) {
            $clienteIds = \App\Models\Crm\Cliente::whereIn('id', $clienteIds)
                ->where('user_id', $user->id)
                ->pluck('id')
                ->toArray();
        }

        $clientes = \App\Models\Crm\Cliente::whereIn('id', $clienteIds)->get()->keyBy('id');

        $links = [];

        foreach ($clienteIds as $clienteId) {
            $cliente = $clientes[$clienteId] ?? null;
            if (!$cliente || !$cliente->email) {
                continue;
            }

            $envio = EncuestaEnvio::firstOrNew([
                'encuesta_id' => $encuestaId,
                'cliente_id'  => $clienteId,
            ]);

            // Regenerar token si ya fue respondida (reenvío)
            if (!$envio->exists || $envio->estado === 'respondida') {
                $envio->token        = Str::uuid()->toString();
                $envio->estado       = 'pendiente';
                $envio->responded_at = null;
            }

            $envio->user_id = $user->id;
            $envio->sent_at = now();
            $envio->save();

            // Cargar relaciones para la vista del correo
            $envio->setRelation('encuesta', $encuesta);
            $envio->setRelation('cliente', $cliente);

            $link = config('app.frontend_url') . '/encuesta/' . $envio->token;

            Mail::to($cliente->email)->queue(new EncuestaEnviadaMail($envio, $link));

            $links[] = [
                'cliente_id'    => $clienteId,
                'cliente_nombre'=> $cliente->nombre,
                'cliente_email' => $cliente->email,
                'token'         => $envio->token,
                'link'          => $link,
                'estado'        => $envio->estado,
            ];
        }

        return $links;
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

    public function resultados(int $encuestaId, $user): array
    {
        $encuesta  = Encuesta::with('preguntas')->findOrFail($encuestaId);
        $esAdmin   = in_array($user->role_id, $this->rolesAdmin);

        $baseEnvios = EncuestaEnvio::where('encuesta_id', $encuestaId)
            ->when(!$esAdmin, fn ($q) => $q->where('user_id', $user->id));

        $totalEnvios      = (clone $baseEnvios)->count();
        $totalRespondidas = (clone $baseEnvios)->where('estado', 'respondida')->count();

        $envioIds = (clone $baseEnvios)->pluck('id');

        $resultadosPorPregunta = $encuesta->preguntas->map(function ($pregunta) use ($encuestaId, $envioIds) {
            $respuestas = EncuestaRespuesta::whereIn('envio_id', $envioIds)
                ->where('pregunta_id', $pregunta->id)
                ->pluck('valor');

            $datos = match ($pregunta->tipo) {
                'escala' => [
                    'promedio'      => round($respuestas->avg(), 2),
                    'distribucion'  => collect(range(1, 5))->mapWithKeys(fn ($n) => [
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

        // Índice de satisfacción: % de respuestas >= 4 en preguntas de tipo escala
        $preguntasEscala  = $encuesta->preguntas->where('tipo', 'escala');
        $indicesSatisfaccion = null;

        if ($preguntasEscala->isNotEmpty()) {
            $totalEscala    = 0;
            $positivasEscala = 0;

            foreach ($preguntasEscala as $pregunta) {
                $vals = EncuestaRespuesta::whereIn('envio_id', $envioIds)
                    ->where('pregunta_id', $pregunta->id)
                    ->pluck('valor');

                $totalEscala    += $vals->count();
                $positivasEscala += $vals->filter(fn ($v) => (int) $v >= 4)->count();
            }

            $indicesSatisfaccion = $totalEscala > 0
                ? round(($positivasEscala / $totalEscala) * 100, 1)
                : null;
        }

        return [
            'encuesta_id'         => $encuestaId,
            'titulo'              => $encuesta->titulo,
            'total_envios'        => $totalEnvios,
            'total_respondidas'   => $totalRespondidas,
            'tasa_respuesta'      => $totalEnvios > 0 ? round(($totalRespondidas / $totalEnvios) * 100, 2) : 0,
            'indice_satisfaccion' => $indicesSatisfaccion,
            'preguntas'           => $resultadosPorPregunta,
        ];
    }

    // ─── HELPERS ─────────────────────────────────────────────────────────────

    private function sincronizarPreguntas(Encuesta $encuesta, array $preguntas): void
    {
        $encuesta->preguntas()->delete();

        foreach ($preguntas as $index => $p) {
            $encuesta->preguntas()->create([
                'texto'     => $p['texto'],
                'tipo'      => $p['tipo'],
                'opciones'  => $p['opciones'] ?? null,
                'orden'     => $p['orden'] ?? $index,
                'requerida' => $p['requerida'] ?? true,
            ]);
        }
    }

    private function autorizarCreador($user): void
    {
        if (!in_array($user->role_id, $this->rolesCreador)) {
            abort(403, 'No tienes permiso para realizar esta acción');
        }
    }
}
