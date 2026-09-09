<?php

namespace App\Services\RegistroDiario;

use App\Models\RegistroDiario\Novedades;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Analiza con IA si hay No Conformidades reiterativas (mismo problema o misma
 * causa raíz que se repite en el tiempo) a partir del historial del módulo
 * Registro Diario (novedad_diaria + hallazgos / planes de acción).
 *
 * No persiste nada: es un análisis bajo demanda. Las Oportunidades de Mejora
 * quedan fuera — solo se analizan filas con clasificacion = NO_CONFORMIDAD.
 */
class AnalisisReincidenciaService
{
    private const MAX_NO_CONFORMIDADES = 120;

    /**
     * @return array{
     *   sin_datos: bool,
     *   periodo: array{desde: string, hasta: string},
     *   total_analizadas: int,
     *   resumen: string,
     *   grupos_reincidentes: array<int, array<string, mixed>>,
     *   no_conformidades_sin_reincidencia: int
     * }
     */
    public function analizar(?int $departamentoId = null, ?string $fechaInicio = null, ?string $fechaFin = null): array
    {
        $hasta = $fechaFin ? Carbon::parse($fechaFin)->endOfDay() : Carbon::now()->endOfDay();
        $desde = $fechaInicio ? Carbon::parse($fechaInicio)->startOfDay() : $hasta->copy()->subMonths(12)->startOfDay();

        $noConformidades = $this->noConformidades($departamentoId, $desde, $hasta);

        $periodo = [
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
        ];

        if (count($noConformidades) < 2) {
            return [
                'sin_datos' => true,
                'periodo' => $periodo,
                'total_analizadas' => count($noConformidades),
                'resumen' => 'No hay suficientes No Conformidades en el periodo para analizar reincidencia.',
                'grupos_reincidentes' => [],
                'no_conformidades_sin_reincidencia' => count($noConformidades),
            ];
        }

        $contexto = [
            'periodo' => $periodo,
            'total_no_conformidades' => count($noConformidades),
            'no_conformidades' => $noConformidades,
        ];

        $respuesta = $this->llamarOpenAi([
            ['role' => 'system', 'content' => $this->promptSistema()],
            ['role' => 'user', 'content' => json_encode($contexto, JSON_UNESCAPED_UNICODE)],
        ]);

        return $this->parsear($respuesta, $periodo, count($noConformidades));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function noConformidades(?int $departamentoId, Carbon $desde, Carbon $hasta): array
    {
        return Novedades::query()
            ->where('clasificacion', 'NO_CONFORMIDAD')
            ->with(['registroDiario.departamento:id,nombre', 'responsable:id,name', 'hallazgos.responsable:id,name'])
            ->whereHas('registroDiario', function ($q) use ($departamentoId, $desde, $hasta) {
                $q->whereBetween('fecha', [$desde, $hasta]);
                if ($departamentoId) {
                    $q->where('departamento_id', $departamentoId);
                }
            })
            ->latest()
            ->limit(self::MAX_NO_CONFORMIDADES)
            ->get()
            ->map(fn (Novedades $n) => array_filter([
                'id' => $n->id,
                'numero_no_conformidad' => $n->numero_no_conformidad,
                'fecha' => $n->registroDiario?->fecha ? Carbon::parse($n->registroDiario->fecha)->toDateString() : null,
                'departamento' => $n->registroDiario?->departamento?->nombre,
                'descripcion' => $n->descripcion,
                'causa' => $n->causa,
                'correccion' => $n->correccion,
                'tipo_accion' => $n->tipo_accion,
                'fuente' => $n->fuentes,
                'estado' => $n->estado,
                'responsable' => $n->responsable?->name,
                'planes_accion' => $n->hallazgos->map(fn ($h) => array_filter([
                    'causa' => $h->causa,
                    'plan_accion' => $h->plan_accion,
                    'estado' => $h->estado,
                    'responsable' => $h->responsable?->name,
                    'fecha_cierre' => $h->fecha_cierre ? (string) $h->fecha_cierre : null,
                ], fn ($v) => $v !== null && $v !== ''))->values()->all(),
            ], fn ($v) => $v !== null && $v !== '' && $v !== []))
            ->values()
            ->all();
    }

    private function llamarOpenAi(array $mensajes): string
    {
        $modelo = config('services.openai.model');
        $payload = [
            'model' => $modelo,
            'messages' => $mensajes,
            'response_format' => ['type' => 'json_object'],
        ];

        // Mismo criterio que InformeRendimientoService / GeneradorPreguntasAuditoriaService.
        if (in_array($modelo, config('services.openai.reasoning_models', []), true)) {
            $payload['reasoning_effort'] = 'none';
        } else {
            $payload['temperature'] = 0.3;
        }

        $response = Http::withToken(config('services.openai.key'))
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', $payload);

        if (! $response->successful()) {
            $detalle = $response->json('error.message', $response->body());
            throw new \RuntimeException('OpenAI respondió con estado ' . $response->status() . ': ' . mb_substr((string) $detalle, 0, 500));
        }

        $texto = trim((string) $response->json('choices.0.message.content', ''));
        if ($texto === '') {
            throw new \RuntimeException('La IA devolvió una respuesta vacía.');
        }

        return $texto;
    }

    private function parsear(string $json, array $periodo, int $total): array
    {
        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw new \RuntimeException('La IA no devolvió un análisis en el formato esperado.');
        }

        $severidadesValidas = ['alta', 'media', 'baja'];
        $grupos = collect($data['grupos_reincidentes'] ?? $data['grupos'] ?? [])
            ->map(function ($g) use ($severidadesValidas) {
                if (! is_array($g)) {
                    return null;
                }

                $novedades = collect($g['novedades'] ?? [])
                    ->map(fn ($nv) => is_array($nv) ? array_filter([
                        'id' => isset($nv['id']) ? (int) $nv['id'] : null,
                        'numero_no_conformidad' => $nv['numero_no_conformidad'] ?? $nv['numero'] ?? null,
                        'fecha' => $nv['fecha'] ?? null,
                    ], fn ($v) => $v !== null && $v !== '') : ['id' => (int) $nv])
                    ->filter(fn ($nv) => ! empty($nv['id']))
                    ->values()
                    ->all();

                $severidad = strtolower(trim((string) ($g['severidad'] ?? '')));

                return [
                    'tema' => mb_substr(trim((string) ($g['tema'] ?? '')), 0, 200),
                    'descripcion' => mb_substr(trim((string) ($g['descripcion'] ?? '')), 0, 800),
                    'cantidad' => (int) ($g['cantidad'] ?? count($novedades)),
                    'departamentos' => array_values(array_filter((array) ($g['departamentos'] ?? []))),
                    'fuentes' => array_values(array_filter((array) ($g['fuentes'] ?? []))),
                    'causa_raiz_comun' => mb_substr(trim((string) ($g['causa_raiz_comun'] ?? $g['causa_comun'] ?? '')), 0, 800),
                    'recomendacion' => mb_substr(trim((string) ($g['recomendacion'] ?? '')), 0, 800),
                    'severidad' => in_array($severidad, $severidadesValidas, true) ? $severidad : 'media',
                    'novedades' => $novedades,
                ];
            })
            ->filter(fn ($g) => $g && $g['tema'] !== '' && count($g['novedades']) >= 2)
            ->sortByDesc(fn ($g) => ['alta' => 3, 'media' => 2, 'baja' => 1][$g['severidad']] ?? 0)
            ->values()
            ->all();

        $enGrupos = collect($grupos)->flatMap(fn ($g) => collect($g['novedades'])->pluck('id'))->unique()->count();

        return [
            'sin_datos' => false,
            'periodo' => $periodo,
            'total_analizadas' => $total,
            'resumen' => mb_substr(trim((string) ($data['resumen'] ?? '')), 0, 1500),
            'grupos_reincidentes' => $grupos,
            'no_conformidades_sin_reincidencia' => max($total - $enGrupos, 0),
        ];
    }

    private function promptSistema(): string
    {
        return "Eres un especialista en sistemas de gestión de calidad (ISO 9001) analizando el "
            . "historial de No Conformidades de una empresa para detectar REINCIDENCIA: el mismo "
            . "problema, o problemas con la misma causa raíz, que se repiten en el tiempo o entre "
            . "áreas.\n\n"
            . "Recibirás un JSON con un periodo y una lista de No Conformidades, cada una con: id, "
            . "número, fecha, departamento, descripción, causa, corrección, fuente, estado y sus "
            . "planes de acción (con causa y estado).\n\n"
            . "Agrupa SOLO las No Conformidades que sean genuinamente reiterativas entre sí (mínimo 2 "
            . "por grupo). Una No Conformidad aislada no es un grupo. Para cada grupo identifica el "
            . "tema, la causa raíz común, en qué departamentos y fuentes aparece, y una recomendación "
            . "concreta (ej. acción correctiva sistémica, revisión de un procedimiento, capacitación). "
            . "Asigna severidad: 'alta' si se repite mucho o los planes de acción previos no la "
            . "evitaron, 'media' o 'baja' según corresponda.\n\n"
            . "Usa EXCLUSIVAMENTE los datos del JSON. Nunca inventes números, fechas, causas ni "
            . "departamentos. Referencia cada No Conformidad de un grupo por su id exacto. Si no "
            . "encuentras reincidencia real, devuelve 'grupos_reincidentes' vacío y explícalo en el "
            . "resumen.\n\n"
            . "Responde ÚNICAMENTE con un objeto JSON válido con esta forma exacta:\n"
            . '{"resumen":"panorama general en 2-4 frases","grupos_reincidentes":[{"tema":"...",'
            . '"descripcion":"...","cantidad":0,"departamentos":["..."],"fuentes":["..."],'
            . '"causa_raiz_comun":"...","recomendacion":"...","severidad":"alta|media|baja",'
            . '"novedades":[{"id":0,"numero_no_conformidad":"...","fecha":"YYYY-MM-DD"}]}]}';
    }
}
