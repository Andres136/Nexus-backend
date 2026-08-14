<?php

namespace App\Services\Nomina;

use App\Events\Nomina\CuestionarioPublicado;
use App\Models\Nomina\Convocatoria;
use App\Models\Nomina\Cuestionario;
use App\Models\Nomina\CuestionarioPregunta;
use App\Models\Nomina\CuestionarioRespuesta;
use App\Models\Nomina\PostulacionConvocatoria;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CuestionarioService
{
    public function pendienteParaUsuario(int $userId): ?Cuestionario
    {
        return Cuestionario::with('convocatoria')
            ->where('estado', 'publicado')
            ->whereHas('convocatoria.postulaciones', fn ($q) => $q->where('user_id', $userId))
            ->whereDoesntHave('respuestas', fn ($q) => $q->where('user_id', $userId)->whereNotNull('enviado_en'))
            ->get()
            ->filter(fn (Cuestionario $c) => now()->lessThan($c->publicado_en->copy()->addSeconds($c->duracion_segundos)))
            ->sortByDesc('publicado_en')
            ->first();
    }

    public function paraConvocatoria(string $convocatoriaUuid): ?Cuestionario
    {
        $convocatoria = Convocatoria::where('uuid', $convocatoriaUuid)->firstOrFail();

        return Cuestionario::with('preguntas')
            ->where('convocatoria_id', $convocatoria->id)
            ->first();
    }

    public function guardar(string $convocatoriaUuid, array $data, int $creadoPor): Cuestionario
    {
        return DB::transaction(function () use ($convocatoriaUuid, $data, $creadoPor) {
            $convocatoria = Convocatoria::where('uuid', $convocatoriaUuid)->firstOrFail();
            $cuestionario = Cuestionario::where('convocatoria_id', $convocatoria->id)->first();

            if ($cuestionario && $cuestionario->estado !== 'borrador') {
                throw ValidationException::withMessages([
                    'estado' => 'Ya no puedes editar un cuestionario publicado o cerrado.',
                ]);
            }

            if (! $cuestionario) {
                $cuestionario = Cuestionario::create([
                    'convocatoria_id' => $convocatoria->id,
                    'titulo' => $data['titulo'],
                    'descripcion' => $data['descripcion'] ?? null,
                    'duracion_segundos' => $data['duracion_segundos'],
                    'creado_por' => $creadoPor,
                ]);
            } else {
                $cuestionario->update([
                    'titulo' => $data['titulo'],
                    'descripcion' => $data['descripcion'] ?? null,
                    'duracion_segundos' => $data['duracion_segundos'],
                ]);
                $cuestionario->preguntas()->delete();
            }

            foreach ($data['preguntas'] as $orden => $pregunta) {
                CuestionarioPregunta::create([
                    'cuestionario_id' => $cuestionario->id,
                    'texto' => $pregunta['texto'],
                    'tipo' => $pregunta['tipo'] ?? 'texto',
                    'opciones' => $pregunta['opciones'] ?? null,
                    'orden' => $orden,
                ]);
            }

            return $cuestionario->fresh('preguntas');
        });
    }

    public function publicar(string $uuid): Cuestionario
    {
        $cuestionario = Cuestionario::with('preguntas')->where('uuid', $uuid)->firstOrFail();

        if ($cuestionario->estado !== 'borrador') {
            throw ValidationException::withMessages([
                'estado' => 'Este cuestionario ya fue publicado.',
            ]);
        }

        if ($cuestionario->preguntas->isEmpty()) {
            throw ValidationException::withMessages([
                'preguntas' => 'Agrega al menos una pregunta antes de publicar.',
            ]);
        }

        $cuestionario->update([
            'estado' => 'publicado',
            'publicado_en' => now(),
        ]);

        $cuestionario = $cuestionario->fresh(['preguntas', 'convocatoria']);

        try {
            broadcast(new CuestionarioPublicado($cuestionario));
        } catch (\Throwable $e) {
            // La publicación ya quedó guardada; si el servidor de websockets
            // no está disponible, los postulantes simplemente no verán el
            // aviso instantáneo y lo detectarán al refrescar/reabrir la
            // página, en vez de tumbar toda la acción de publicar.
            Log::error('No se pudo emitir el evento de cuestionario publicado', [
                'cuestionario_id' => $cuestionario->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $cuestionario;
    }

    public function cerrar(string $uuid): Cuestionario
    {
        $cuestionario = Cuestionario::where('uuid', $uuid)->firstOrFail();
        $cuestionario->update(['estado' => 'cerrado', 'cerrado_en' => now()]);

        return $cuestionario->fresh();
    }

    public function despublicar(string $uuid): Cuestionario
    {
        $cuestionario = Cuestionario::where('uuid', $uuid)->firstOrFail();

        if ($cuestionario->estado === 'borrador') {
            throw ValidationException::withMessages([
                'estado' => 'Este cuestionario todavía no ha sido publicado.',
            ]);
        }

        $cuestionario->update([
            'estado' => 'borrador',
            'publicado_en' => null,
            'cerrado_en' => null,
        ]);

        return $cuestionario->fresh(['preguntas']);
    }

    public function paraPostulante(string $convocatoriaUuid, int $userId): array
    {
        $convocatoria = Convocatoria::where('uuid', $convocatoriaUuid)->firstOrFail();

        $esPostulante = PostulacionConvocatoria::where('convocatoria_id', $convocatoria->id)
            ->where('user_id', $userId)
            ->exists();

        if (! $esPostulante) {
            throw ValidationException::withMessages([
                'convocatoria' => 'Debes postularte a esta convocatoria antes de acceder al cuestionario.',
            ]);
        }

        $cuestionario = Cuestionario::where('convocatoria_id', $convocatoria->id)->first();

        if (! $cuestionario) {
            return ['cuestionario' => null, 'mis_respuestas' => []];
        }

        // Solo las respuestas ya enviadas cuentan como "ya respondió"; las
        // que quedaron autoguardadas en borrador (enviado_en null) se
        // devuelven aparte para poder rellenar el formulario sin bloquear
        // el envío final.
        $respuestasUsuario = CuestionarioRespuesta::where('cuestionario_id', $cuestionario->id)
            ->where('user_id', $userId)
            ->get(['pregunta_id', 'valor', 'enviado_en']);

        $misRespuestas = $respuestasUsuario->whereNotNull('enviado_en')->values();
        $miBorrador = $respuestasUsuario->whereNull('enviado_en')->values();

        // Antes de publicarse no se exponen las preguntas, solo el estado
        // de "sala de espera" (evita filtrar el contenido antes de tiempo).
        if ($cuestionario->estado === 'borrador') {
            $cuestionario->setRelation('preguntas', collect());
        } else {
            $cuestionario->load('preguntas');
        }

        return ['cuestionario' => $cuestionario, 'mis_respuestas' => $misRespuestas, 'mi_borrador' => $miBorrador];
    }

    /**
     * Autoguardado mientras el postulante escribe: upsert por pregunta sin
     * marcar enviado_en, para no dejar nada perdido si se cierra el
     * cuestionario o se agota el tiempo antes del envío final.
     */
    public function guardarBorrador(string $cuestionarioUuid, int $userId, array $respuestas): void
    {
        $cuestionario = Cuestionario::where('uuid', $cuestionarioUuid)->firstOrFail();

        // El frontend intenta un guardado inmediato (sin esperar el debounce)
        // justo cuando se acaba el tiempo o cuando detecta que Talento
        // Humano cerró el cuestionario, para no perder lo último escrito.
        // Ese guardado puede llegar unos segundos después del corte exacto
        // por la latencia de red, así que se acepta un margen de gracia en
        // vez de rechazarlo silenciosamente.
        $margenGracia = 20;
        $puedeGuardar = match (true) {
            $cuestionario->estado === 'publicado' => now()->lessThanOrEqualTo(
                $cuestionario->publicado_en->copy()->addSeconds($cuestionario->duracion_segundos)->addSeconds($margenGracia)
            ),
            $cuestionario->estado === 'cerrado' && $cuestionario->cerrado_en => now()->lessThanOrEqualTo(
                $cuestionario->cerrado_en->copy()->addSeconds($margenGracia)
            ),
            default => false,
        };

        if (! $puedeGuardar) {
            return;
        }

        $yaEnviado = CuestionarioRespuesta::where('cuestionario_id', $cuestionario->id)
            ->where('user_id', $userId)
            ->whereNotNull('enviado_en')
            ->exists();

        if ($yaEnviado) {
            return;
        }

        $preguntasValidas = $cuestionario->preguntas()->pluck('id')->all();

        foreach ($respuestas as $respuesta) {
            if (! in_array((int) $respuesta['pregunta_id'], $preguntasValidas, true)) {
                continue;
            }

            CuestionarioRespuesta::updateOrCreate(
                [
                    'cuestionario_id' => $cuestionario->id,
                    'pregunta_id' => $respuesta['pregunta_id'],
                    'user_id' => $userId,
                ],
                ['valor' => (string) ($respuesta['valor'] ?? '')]
            );
        }
    }

    public function guardarRespuestas(string $cuestionarioUuid, int $userId, array $respuestas): Collection
    {
        return DB::transaction(function () use ($cuestionarioUuid, $userId, $respuestas) {
            $cuestionario = Cuestionario::where('uuid', $cuestionarioUuid)->firstOrFail();

            if ($cuestionario->estado !== 'publicado') {
                throw ValidationException::withMessages([
                    'estado' => 'Este cuestionario no está disponible para responder.',
                ]);
            }

            if (now()->greaterThan($cuestionario->publicado_en->copy()->addSeconds($cuestionario->duracion_segundos))) {
                throw ValidationException::withMessages([
                    'tiempo' => 'Se agotó el tiempo para responder este cuestionario.',
                ]);
            }

            $yaRespondio = CuestionarioRespuesta::where('cuestionario_id', $cuestionario->id)
                ->where('user_id', $userId)
                ->whereNotNull('enviado_en')
                ->exists();

            if ($yaRespondio) {
                throw ValidationException::withMessages([
                    'respuestas' => 'Ya enviaste tus respuestas para este cuestionario.',
                ]);
            }

            $preguntasValidas = $cuestionario->preguntas()->pluck('id')->all();

            foreach ($respuestas as $respuesta) {
                if (! in_array((int) $respuesta['pregunta_id'], $preguntasValidas, true)) {
                    continue;
                }

                CuestionarioRespuesta::updateOrCreate(
                    [
                        'cuestionario_id' => $cuestionario->id,
                        'pregunta_id' => $respuesta['pregunta_id'],
                        'user_id' => $userId,
                    ],
                    [
                        'valor' => $respuesta['valor'],
                        'enviado_en' => now(),
                    ]
                );
            }

            return CuestionarioRespuesta::where('cuestionario_id', $cuestionario->id)
                ->where('user_id', $userId)
                ->get();
        });
    }

    public function respuestasPorPostulante(string $cuestionarioUuid): Collection
    {
        $cuestionario = Cuestionario::where('uuid', $cuestionarioUuid)->firstOrFail();

        return CuestionarioRespuesta::with(['usuario', 'pregunta', 'calificador'])
            ->where('cuestionario_id', $cuestionario->id)
            ->whereNotNull('enviado_en')
            ->get()
            ->groupBy('user_id')
            ->values();
    }

    public function respuestasDeUsuario(string $cuestionarioUuid, int $userId): Collection
    {
        $cuestionario = Cuestionario::where('uuid', $cuestionarioUuid)->firstOrFail();

        return CuestionarioRespuesta::with(['usuario', 'pregunta', 'calificador'])
            ->where('cuestionario_id', $cuestionario->id)
            ->where('user_id', $userId)
            ->whereNotNull('enviado_en')
            ->get();
    }

    public function calificar(int $respuestaId, int $calificacion, int $calificadoPor): CuestionarioRespuesta
    {
        $respuesta = CuestionarioRespuesta::findOrFail($respuestaId);
        $respuesta->update([
            'calificacion' => $calificacion,
            'calificado_por' => $calificadoPor,
            'calificado_en' => now(),
        ]);

        return $respuesta->fresh(['usuario', 'pregunta', 'calificador']);
    }
}
