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
    private const MAX_CANDIDATAS = 60;

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
     * Verifica, al momento de registrar un hallazgo (No Conformidad u Oportunidad
     * de Mejora), si ya existe uno similar en el historial del mismo tipo (mismo
     * problema o misma causa). No persiste nada.
     *
     * @return array{
     *   hay_similares: bool,
     *   mensaje: string,
     *   nota_sugerida: string,
     *   similares: array<int, array{id:int, numero_no_conformidad:?string, fecha:?string, departamento:?string, motivo:string}>
     * }
     */
    public function verificarCandidata(
        string $descripcion,
        ?int $departamentoId = null,
        ?string $causa = null,
        string $clasificacion = 'NO_CONFORMIDAD'
    ): array {
        $descripcion = trim($descripcion);
        $clasificacion = in_array($clasificacion, ['NO_CONFORMIDAD', 'OPORTUNIDAD_MEJORA'], true)
            ? $clasificacion
            : 'NO_CONFORMIDAD';
        $etiqueta = $clasificacion === 'OPORTUNIDAD_MEJORA' ? 'Oportunidad de Mejora' : 'No Conformidad';

        if (mb_strlen($descripcion) < 10) {
            return [
                'hay_similares' => false,
                'mensaje' => "Describe la {$etiqueta} con más detalle para poder buscar similares.",
                'nota_sugerida' => '',
                'similares' => [],
            ];
        }

        $historico = $this->historicoParaComparar($departamentoId, $clasificacion);

        if (empty($historico)) {
            return [
                'hay_similares' => false,
                'mensaje' => "No hay {$etiqueta}s previas registradas para comparar.",
                'nota_sugerida' => '',
                'similares' => [],
            ];
        }

        $contexto = [
            'hallazgo_nuevo' => array_filter([
                'tipo' => $etiqueta,
                'descripcion' => $descripcion,
                'causa' => $causa ?: null,
            ]),
            'historico' => $historico,
        ];

        $respuesta = $this->llamarOpenAi([
            ['role' => 'system', 'content' => $this->promptVerificacion($etiqueta)],
            ['role' => 'user', 'content' => json_encode($contexto, JSON_UNESCAPED_UNICODE)],
        ]);

        // Red de seguridad: si la descripción es casi idéntica a una previa, se
        // marca como similar aunque la IA no lo haya detectado (p. ej. textos muy
        // cortos o vagos donde la IA prefiere no arriesgar).
        $forzados = $this->duplicadosPorTexto($descripcion, $historico);

        return $this->parsearVerificacion($respuesta, $historico, $etiqueta, $forzados);
    }

    /**
     * @param  array<int, array<string, mixed>>  $historico
     * @return array<int, array{id: int, motivo: string}>
     */
    private function duplicadosPorTexto(string $descripcion, array $historico): array
    {
        $normalizar = fn ($s) => trim((string) preg_replace('/\s+/', ' ', mb_strtolower((string) $s)));
        $nueva = $normalizar($descripcion);

        if (mb_strlen($nueva) < 8) {
            return [];
        }

        $dups = [];
        foreach ($historico as $h) {
            $previa = $normalizar($h['descripcion'] ?? '');
            if ($previa === '') {
                continue;
            }

            $iguales = $nueva === $previa
                || (mb_strlen($previa) >= 8 && (str_contains($nueva, $previa) || str_contains($previa, $nueva)));

            $pct = 0.0;
            if (! $iguales) {
                similar_text($nueva, $previa, $pct);
            }

            if ($iguales || $pct >= 82) {
                $dups[] = [
                    'id' => (int) $h['id'],
                    'motivo' => $iguales
                        ? 'La descripción es prácticamente idéntica a una previa.'
                        : 'La descripción es muy parecida a una previa (' . round($pct) . '% de similitud).',
                ];
            }
        }

        return $dups;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function historicoParaComparar(?int $departamentoId, string $clasificacion): array
    {
        $desde = Carbon::now()->subMonths(18)->startOfDay();

        return Novedades::query()
            ->where('clasificacion', $clasificacion)
            ->with(['registroDiario.departamento:id,nombre'])
            ->whereHas('registroDiario', function ($q) use ($departamentoId, $desde) {
                $q->where('fecha', '>=', $desde);
                if ($departamentoId) {
                    $q->where('departamento_id', $departamentoId);
                }
            })
            ->latest()
            ->limit(self::MAX_CANDIDATAS)
            ->get()
            ->map(fn (Novedades $n) => array_filter([
                'id' => $n->id,
                'numero_no_conformidad' => $n->numero_no_conformidad,
                'fecha' => $n->registroDiario?->fecha ? Carbon::parse($n->registroDiario->fecha)->toDateString() : null,
                'departamento' => $n->registroDiario?->departamento?->nombre,
                'descripcion' => $n->descripcion,
                'causa' => $n->causa,
            ], fn ($v) => $v !== null && $v !== ''))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $historico
     * @param  array<int, array{id: int, motivo: string}>  $forzados
     */
    private function parsearVerificacion(string $json, array $historico, string $etiqueta, array $forzados = []): array
    {
        $abrev = $etiqueta === 'Oportunidad de Mejora' ? 'OM' : 'NC';
        $data = json_decode($json, true);
        $idsValidos = collect($historico)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $porId = collect($historico)->keyBy('id');

        $similares = collect(is_array($data) ? ($data['similares'] ?? []) : [])
            ->map(function ($s) use ($idsValidos, $porId) {
                $id = (int) (is_array($s) ? ($s['id'] ?? 0) : $s);
                if (! in_array($id, $idsValidos, true)) {
                    return null;
                }
                $fuente = $porId->get($id, []);

                return [
                    'id' => $id,
                    'numero_no_conformidad' => $fuente['numero_no_conformidad'] ?? null,
                    'fecha' => $fuente['fecha'] ?? null,
                    'departamento' => $fuente['departamento'] ?? null,
                    'motivo' => mb_substr(trim((string) (is_array($s) ? ($s['motivo'] ?? '') : '')), 0, 400),
                ];
            })
            ->filter()
            ->values()
            ->all();

        // Merge de las coincidencias forzadas por similitud de texto.
        $yaListados = collect($similares)->pluck('id')->all();
        foreach ($forzados as $f) {
            if (in_array((int) $f['id'], $idsValidos, true) && ! in_array((int) $f['id'], $yaListados, true)) {
                $fuente = $porId->get($f['id'], []);
                $similares[] = [
                    'id' => (int) $f['id'],
                    'numero_no_conformidad' => $fuente['numero_no_conformidad'] ?? null,
                    'fecha' => $fuente['fecha'] ?? null,
                    'departamento' => $fuente['departamento'] ?? null,
                    'motivo' => $f['motivo'],
                ];
            }
        }

        $similares = collect($similares)->unique('id')->values()->all();

        $haySimilares = ! empty($similares);
        $mensajeIa = is_array($data) ? trim((string) ($data['mensaje'] ?? '')) : '';
        if (! empty($forzados)) {
            // Si hubo casi-duplicados de texto, el mensaje de la IA (que pudo decir
            // "sin coincidencias") ya no aplica.
            $mensajeIa = 'La descripción coincide casi textualmente con una ' . $etiqueta . ' ya registrada.';
        }

        return [
            'hay_similares' => $haySimilares,
            'mensaje' => mb_substr($mensajeIa !== '' ? $mensajeIa : ($haySimilares
                ? "Se encontraron {$etiqueta}s similares en el historial."
                : "No se encontraron {$etiqueta}s similares."), 0, 800),
            'nota_sugerida' => $haySimilares
                ? mb_substr('Posible reincidencia de: ' . collect($similares)
                    ->map(fn ($s) => ($s['numero_no_conformidad'] ?: ("{$abrev} #" . $s['id'])) . ($s['fecha'] ? " ({$s['fecha']})" : ''))
                    ->implode('; '), 0, 2000)
                : '',
            'similares' => $similares,
        ];
    }

    private function promptVerificacion(string $etiqueta): string
    {
        return "Eres un analista de calidad (ISO 9001). Recibes un hallazgo NUEVO del tipo \"{$etiqueta}\" "
            . "que se está registrando y un histórico de {$etiqueta}s previas (cada una con id, número, "
            . "fecha, departamento, descripción y causa).\n\n"
            . "Determina si el nuevo es REINCIDENTE de alguno previo: mismo problema de fondo o misma "
            . "causa raíz, aunque el texto no sea idéntico. Sé estricto: solo marca como similares los "
            . "que realmente representan el mismo asunto. Si ninguno coincide, devuelve la lista "
            . "vacía.\n\n"
            . "Usa EXCLUSIVAMENTE el histórico recibido. Referencia cada coincidencia por su id exacto y "
            . "explica en 'motivo' por qué es el mismo asunto.\n\n"
            . "Responde ÚNICAMENTE con un objeto JSON válido con esta forma exacta:\n"
            . '{"mensaje":"frase corta para el usuario","similares":[{"id":0,"motivo":"por qué es el mismo asunto"}]}';
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
