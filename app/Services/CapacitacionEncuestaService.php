<?php

namespace App\Services;

use App\Mail\CapacitacionEncuestaMail;
use App\Models\Capacitacion;
use App\Models\CapacitacionEncuesta;
use App\Models\CapacitacionEncuestaEnvio;
use App\Models\CapacitacionEncuestaRespuesta;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CapacitacionEncuestaService
{
    public function index(array $filters)
    {
        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = CapacitacionEncuesta::query()
            ->with([
                'capacitacion:id,uuid,titulo,fecha_realizacion',
                'creador:id,name,email',
                'preguntas',
            ])
            ->withCount([
                'envios',
                'envios as respondidas_count' => fn ($query) => $query->where('estado', 'respondida'),
            ])
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($q) use ($search) {
                    $q->where('titulo', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%")
                        ->orWhereHas('capacitacion', fn ($capQuery) =>
                            $capQuery->where('titulo', 'like', "%{$search}%"));
                });
            })
            ->when(!empty($filters['capacitacion_uuid']), function ($query) use ($filters) {
                $query->whereHas('capacitacion', fn ($capQuery) =>
                    $capQuery->where('uuid', $filters['capacitacion_uuid']));
            })
            ->when(!empty($filters['estado']), fn ($query) => $query->where('estado', $filters['estado']))
            ->latest()
            ->paginate(20);

        return $paginator->through(function ($encuesta) {
            $total = (int) ($encuesta->envios_count ?? 0);
            $respondidas = (int) ($encuesta->respondidas_count ?? 0);
            $encuesta->setAttribute('porcentaje_respuesta', $total > 0 ? round(($respondidas / $total) * 100, 1) : 0);
            return $encuesta;
        });
    }

    public function store(array $data, User $user): CapacitacionEncuesta
    {
        $capacitacion = $this->resolverCapacitacion($data);

        return DB::transaction(function () use ($data, $user, $capacitacion) {
            $encuesta = CapacitacionEncuesta::create([
                'capacitacion_id' => $capacitacion->id,
                'user_id' => $user->id,
                'titulo' => $data['titulo'],
                'descripcion' => $data['descripcion'] ?? null,
                'estado' => $data['estado'] ?? 'activa',
            ]);

            $this->sincronizarPreguntas($encuesta, $data['preguntas']);

            return $this->show($encuesta->uuid);
        });
    }

    public function show(string $uuid): CapacitacionEncuesta
    {
        return CapacitacionEncuesta::with([
            'capacitacion:id,uuid,titulo,fecha_realizacion',
            'creador:id,name,email',
            'preguntas',
            'envios.usuario:id,name,email',
        ])
            ->withCount([
                'envios',
                'envios as respondidas_count' => fn ($query) => $query->where('estado', 'respondida'),
            ])
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function update(string $uuid, array $data, User $user): CapacitacionEncuesta
    {
        return DB::transaction(function () use ($uuid, $data, $user) {
            $encuesta = CapacitacionEncuesta::where('uuid', $uuid)->lockForUpdate()->firstOrFail();
            $this->validarCreador($encuesta, $user);

            $capacitacion = $this->resolverCapacitacion($data);

            $encuesta->update([
                'capacitacion_id' => $capacitacion->id,
                'titulo' => $data['titulo'],
                'descripcion' => $data['descripcion'] ?? null,
                'estado' => $data['estado'] ?? $encuesta->estado,
            ]);

            $this->sincronizarPreguntas($encuesta, $data['preguntas']);

            return $this->show($encuesta->uuid);
        });
    }

    public function destroy(string $uuid, User $user): void
    {
        $encuesta = CapacitacionEncuesta::where('uuid', $uuid)->firstOrFail();
        $this->validarCreador($encuesta, $user);
        $encuesta->delete();
    }

    public function usuarios(array $filters): Collection
    {
        return User::query()
            ->select('id', 'name', 'email', 'sede_id')
            ->where('estado_id', 3)
            ->whereNotNull('email')
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(!empty($filters['sede_id']), fn ($query) => $query->where('sede_id', $filters['sede_id']))
            ->orderBy('name')
            ->limit(100)
            ->get();
    }

    public function enviar(string $uuid, array $userIds, User $remitente): array
    {
        $encuesta = CapacitacionEncuesta::with(['preguntas', 'capacitacion'])->where('uuid', $uuid)->firstOrFail();

        if ($encuesta->estado !== 'activa') {
            abort(422, 'La encuesta está inactiva.');
        }

        if ($encuesta->preguntas->isEmpty()) {
            abort(422, 'La encuesta debe tener al menos una pregunta.');
        }

        $usuarios = User::whereIn('id', $userIds)
            ->where('estado_id', 3)
            ->get()
            ->keyBy('id');

        $links = [];
        $excluidos = [];

        foreach ($userIds as $userId) {
            $usuario = $usuarios->get($userId);

            if (!$usuario) {
                $excluidos[] = ['user_id' => $userId, 'razon' => 'Usuario inactivo o no encontrado'];
                continue;
            }

            if (empty($usuario->email)) {
                $excluidos[] = ['user_id' => $userId, 'nombre' => $usuario->name, 'razon' => 'Sin correo electrónico'];
                continue;
            }

            $envio = CapacitacionEncuestaEnvio::firstOrNew([
                'encuesta_id' => $encuesta->id,
                'user_id' => $usuario->id,
            ]);

            if ($envio->exists && $envio->estado === 'respondida') {
                $excluidos[] = ['user_id' => $usuario->id, 'nombre' => $usuario->name, 'razon' => 'Ya respondió la encuesta'];
                continue;
            }

            $envio->token = Str::uuid()->toString();
            $envio->estado = 'pendiente';
            $envio->enviado_por = $remitente->id;
            $envio->sent_at = now();
            $envio->responded_at = null;
            $envio->save();

            $envio->setRelation('encuesta', $encuesta);
            $envio->setRelation('usuario', $usuario);

            $link = rtrim((string) config('app.frontend_url'), '/') . '/capacitacion-encuesta/' . $envio->token;
            Mail::to($usuario->email)->queue(new CapacitacionEncuestaMail($envio, $link));

            $links[] = [
                'user_id' => $usuario->id,
                'nombre' => $usuario->name,
                'email' => $usuario->email,
                'link' => $link,
            ];
        }

        if (!empty($links) && $encuesta->capacitacion && $encuesta->capacitacion->estado !== 'realizada') {
            $encuesta->capacitacion->update(['estado' => 'realizada']);
        }

        return ['links' => $links, 'excluidos' => $excluidos];
    }

    public function showPublica(string $token): CapacitacionEncuestaEnvio
    {
        $envio = CapacitacionEncuestaEnvio::with([
            'usuario:id,name,email',
            'encuesta.capacitacion:id,uuid,titulo,fecha_realizacion',
            'encuesta.preguntas' => fn ($query) => $query->orderBy('orden')
                ->select(['id', 'encuesta_id', 'texto', 'tipo', 'opciones', 'orden', 'requerida', 'max_escala']),
        ])->where('token', $token)->firstOrFail();

        if ($envio->estado !== 'pendiente') {
            abort(410, 'Este enlace ya fue respondido o venció.');
        }

        return $envio;
    }

    public function responderPublica(string $token, array $respuestas): void
    {
        DB::transaction(function () use ($token, $respuestas) {
            $envio = CapacitacionEncuestaEnvio::with('encuesta.preguntas')
                ->where('token', $token)
                ->lockForUpdate()
                ->firstOrFail();

            if ($envio->estado !== 'pendiente') {
                abort(410, 'Este enlace ya fue respondido o venció.');
            }

            $respuestasPorPregunta = collect($respuestas)->keyBy('pregunta_id');

            foreach ($envio->encuesta->preguntas as $pregunta) {
                if ($pregunta->requerida && !$respuestasPorPregunta->has($pregunta->id)) {
                    abort(422, 'Faltan preguntas requeridas por responder.');
                }
            }

            foreach ($respuestas as $respuesta) {
                CapacitacionEncuestaRespuesta::create([
                    'envio_id' => $envio->id,
                    'pregunta_id' => $respuesta['pregunta_id'],
                    'valor' => $respuesta['valor'],
                ]);
            }

            $envio->update([
                'estado' => 'respondida',
                'responded_at' => now(),
            ]);
        });
    }

    public function resultados(string $uuid): array
    {
        $encuesta = CapacitacionEncuesta::with(['preguntas', 'capacitacion:id,uuid,titulo'])->where('uuid', $uuid)->firstOrFail();
        $envios = CapacitacionEncuestaEnvio::with('usuario:id,name,email')
            ->whereHas('usuario', fn ($q) => $q->where('estado_id', 3))
            ->where('encuesta_id', $encuesta->id)
            ->get();
        $envioIds = $envios->pluck('id');

        $resultados = $encuesta->preguntas->map(function ($pregunta) use ($envioIds) {
            $respuestas = CapacitacionEncuestaRespuesta::whereIn('envio_id', $envioIds)
                ->where('pregunta_id', $pregunta->id)
                ->pluck('valor');

            $promedio = null;
            if ($pregunta->tipo === 'escala' && $respuestas->count() > 0) {
                $promedio = round($respuestas->map(fn ($value) => (float) $value)->avg(), 2);
            }

            $aciertos = null;
            if ($pregunta->tipo === 'opcion_multiple' && $pregunta->respuesta_correcta && $respuestas->count() > 0) {
                $aciertos = $respuestas->filter(fn ($v) => $v === $pregunta->respuesta_correcta)->count();
            }

            return [
                'pregunta_id' => $pregunta->id,
                'texto' => $pregunta->texto,
                'tipo' => $pregunta->tipo,
                'total_respuestas' => $respuestas->count(),
                'promedio' => $promedio,
                'respuesta_correcta' => $pregunta->respuesta_correcta,
                'aciertos' => $aciertos,
                'distribucion' => $respuestas->countBy()->map(fn ($total, $valor) => [
                    'valor' => $valor,
                    'total' => $total,
                ])->values(),
                'respuestas_texto' => $pregunta->tipo === 'texto' ? $respuestas->values() : [],
            ];
        });

        return [
            'encuesta' => $encuesta,
            'total_envios' => $envios->count(),
            'total_respondidas' => $envios->where('estado', 'respondida')->count(),
            'envios' => $envios,
            'resultados' => $resultados,
        ];
    }

    private function resolverCapacitacion(array $data): Capacitacion
    {
        if (!empty($data['capacitacion_id'])) {
            return Capacitacion::findOrFail($data['capacitacion_id']);
        }

        return Capacitacion::where('uuid', $data['capacitacion_uuid'])->firstOrFail();
    }

    private function sincronizarPreguntas(CapacitacionEncuesta $encuesta, array $preguntas): void
    {
        $encuesta->preguntas()->delete();

        foreach ($preguntas as $index => $pregunta) {
            $encuesta->preguntas()->create([
                ...Arr::only($pregunta, ['texto', 'tipo', 'opciones', 'requerida', 'max_escala', 'respuesta_correcta']),
                'orden' => $pregunta['orden'] ?? $index,
                'opciones' => $pregunta['tipo'] === 'opcion_multiple' ? ($pregunta['opciones'] ?? []) : null,
                'max_escala' => $pregunta['tipo'] === 'escala' ? ($pregunta['max_escala'] ?? 5) : null,
                'respuesta_correcta' => $pregunta['tipo'] === 'opcion_multiple' ? ($pregunta['respuesta_correcta'] ?? null) : null,
            ]);
        }
    }

    private function validarCreador(CapacitacionEncuesta $encuesta, User $user): void
    {
        if ((int) $encuesta->user_id !== (int) $user->id) {
            abort(403, 'Solo el usuario que creó la encuesta puede modificarla.');
        }
    }
}
