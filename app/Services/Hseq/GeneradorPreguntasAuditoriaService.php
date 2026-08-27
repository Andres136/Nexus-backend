<?php

namespace App\Services\Hseq;

use App\Models\Departamentos;
use App\Models\DocumentoMaestro;
use App\Models\Documentos;
use App\Models\Hseq\AuditoriaPregunta;
use App\Models\Hseq\ClausulaIso;
use App\Models\Hseq\ProductoNoConforme;
use App\Models\RegistroDiario\Novedades;
use App\Models\RegistroDiario\Preguntas;
use Illuminate\Support\Facades\Http;

/**
 * Genera preguntas de auditoría propuestas por IA a partir de los requisitos
 * (cláusulas ISO) seleccionados y, opcionalmente, del proceso auditado.
 *
 * El "proceso auditado" es siempre un departamento (mismo criterio que
 * AuditoriaPregunta::proceso_id → departamentos): a partir de él se aterriza la
 * IA en documentación real (listado maestro + documentos de sus procesos) y en
 * el historial de productos no conformes / planes de acción del área.
 *
 * Nada se persiste aquí: el auditor revisa, edita y guarda cada pregunta con el
 * flujo normal de AuditoriaService::agregarPregunta().
 */
class GeneradorPreguntasAuditoriaService
{
    private const MAX_DOCS = 40;
    private const MAX_NO_CONFORMIDADES = 15;
    private const MAX_PREGUNTAS_PREVIAS = 10;

    /**
     * @param  array<int>  $clausulaIds  requisitos (cláusulas ISO) a auditar
     * @param  int|null  $departamentoId  proceso auditado (departamento) para aterrizar la IA
     * @return array<int, array{pregunta: string, enfoque: string, justificacion: string}>
     */
    public function sugerir(array $clausulaIds, ?int $departamentoId = null, int $cantidad = 5): array
    {
        $cantidad = max(3, min(10, $cantidad));
        $requisitos = $this->requisitos($clausulaIds);

        if (empty($requisitos)) {
            throw new \RuntimeException('Selecciona al menos un requisito (cláusula ISO) válido.');
        }

        $contexto = [
            'cantidad_solicitada' => $cantidad,
            'requisitos' => $requisitos,
            'proceso_auditado' => $departamentoId ? $this->contextoProceso($departamentoId) : null,
            'preguntas_previas_mismos_requisitos' => $this->preguntasPrevias($clausulaIds),
        ];

        $respuesta = $this->llamarOpenAi([
            ['role' => 'system', 'content' => $this->promptSistema($cantidad)],
            ['role' => 'user', 'content' => json_encode($contexto, JSON_UNESCAPED_UNICODE)],
        ]);

        return $this->parsear($respuesta, $cantidad);
    }

    /** @param array<int> $clausulaIds */
    private function requisitos(array $clausulaIds): array
    {
        return ClausulaIso::with('norma:id,nombre')
            ->whereIn('id', $clausulaIds)
            ->where('activa', true)
            ->get()
            ->map(fn (ClausulaIso $c) => [
                'norma' => $c->norma?->nombre,
                'codigo' => $c->codigo,
                'descripcion' => $c->descripcion,
            ])
            ->values()
            ->all();
    }

    private function contextoProceso(int $departamentoId): ?array
    {
        $departamento = Departamentos::with('procesos:id,nombre,departamento_id')->find($departamentoId);
        if (! $departamento) {
            return null;
        }

        $procesoIds = $departamento->procesos->pluck('id');

        $documentosMaestros = DocumentoMaestro::where('departamento_id', $departamentoId)
            ->orderByDesc('fecha_actualizacion')
            ->limit(self::MAX_DOCS)
            ->get(['nombre', 'codigo', 'tipo_documento', 'version'])
            ->map(fn ($d) => array_filter([
                'codigo' => $d->codigo,
                'nombre' => $d->nombre,
                'tipo' => $d->tipo_documento,
                'version' => $d->version,
            ]))
            ->all();

        $documentosProceso = $procesoIds->isEmpty() ? [] : Documentos::whereIn('proceso_id', $procesoIds)
            ->latest()
            ->limit(self::MAX_DOCS)
            ->get(['nombre', 'version', 'observaciones'])
            ->map(fn ($d) => array_filter([
                'nombre' => $d->nombre,
                'version' => $d->version,
                'observaciones' => $d->observaciones,
            ]))
            ->all();

        $productosNoConformes = $procesoIds->isEmpty() ? [] : ProductoNoConforme::with('analisis')
            ->whereIn('proceso_id', $procesoIds)
            ->latest('fecha_reporte')
            ->limit(self::MAX_NO_CONFORMIDADES)
            ->get()
            ->map(fn (ProductoNoConforme $pnc) => array_filter([
                'fecha_reporte' => $pnc->fecha_reporte ? (string) $pnc->fecha_reporte : null,
                'descripcion' => $pnc->descripcion_inicial,
                'tipo_falla' => $pnc->tipo_falla,
                'origen' => $pnc->origen,
                'causa_raiz' => $pnc->analisis?->causa_raiz,
                'acciones_correctivas' => $pnc->analisis?->acciones_correctivas,
                'acciones_preventivas' => $pnc->analisis?->acciones_preventivas,
                'plan_accion_cerrado' => $pnc->analisis?->fecha_cierre ? true : false,
            ], fn ($v) => $v !== null && $v !== ''))
            ->all();

        return [
            'departamento' => $departamento->nombre,
            'procesos' => $departamento->procesos->pluck('nombre')->all(),
            'documentos_maestros' => $documentosMaestros,
            'documentos_proceso' => $documentosProceso,
            'preguntas_seguimiento_diario' => $this->preguntasSeguimientoDiario($departamentoId),
            'no_conformidades_diarias' => $this->noConformidadesDiarias($departamentoId),
            'productos_no_conformes' => $productosNoConformes,
        ];
    }

    /**
     * Preguntas del checklist de seguimiento diario configuradas para el departamento
     * (módulo Registro Diario). Sirven como referencia de qué se verifica día a día.
     */
    private function preguntasSeguimientoDiario(int $departamentoId): array
    {
        return Preguntas::where('departamento_id', $departamentoId)
            ->pluck('pregunta')
            ->all();
    }

    /**
     * No conformidades / novedades del registro diario del departamento, con sus planes
     * de acción (hallazgo_novedades). La relación con el departamento es a través del
     * registro_diario padre.
     */
    private function noConformidadesDiarias(int $departamentoId): array
    {
        return Novedades::with('hallazgos.responsable:id,name')
            ->whereHas('registroDiario', fn ($q) => $q->where('departamento_id', $departamentoId))
            ->latest()
            ->limit(self::MAX_NO_CONFORMIDADES)
            ->get()
            ->map(fn (Novedades $n) => array_filter([
                'numero_no_conformidad' => $n->numero_no_conformidad,
                'descripcion' => $n->descripcion,
                'correccion' => $n->correccion,
                'causa' => $n->causa,
                'tipo_accion' => $n->tipo_accion,
                'fuentes' => $n->fuentes,
                'estado' => $n->estado,
                // Cada hallazgo de la no conformidad con su plan de acción.
                'hallazgos' => $n->hallazgos->map(fn ($h) => array_filter([
                    'causa' => $h->causa,
                    'plan_accion' => $h->plan_accion,
                    'responsable' => $h->responsable?->name,
                    'estado' => $h->estado,
                    'fecha_cierre' => $h->fecha_cierre ? (string) $h->fecha_cierre : null,
                    'observaciones' => $h->observaciones,
                ], fn ($v) => $v !== null && $v !== ''))->values()->all(),
            ], fn ($v) => $v !== null && $v !== '' && $v !== []))
            ->all();
    }

    /** @param array<int> $clausulaIds */
    private function preguntasPrevias(array $clausulaIds): array
    {
        return AuditoriaPregunta::whereNotNull('pregunta')
            ->whereHas('clausulas', fn ($q) => $q->whereIn('clausulas_iso.id', $clausulaIds))
            ->latest()
            ->limit(self::MAX_PREGUNTAS_PREVIAS)
            ->pluck('pregunta')
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

        // Mismo criterio que InformeRendimientoService: los modelos de razonamiento
        // no aceptan 'temperature' y requieren desactivar el razonamiento.
        if (in_array($modelo, config('services.openai.reasoning_models', []), true)) {
            $payload['reasoning_effort'] = 'none';
        } else {
            $payload['temperature'] = 0.4;
        }

        $response = Http::withToken(config('services.openai.key'))
            ->timeout(40)
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

    /**
     * @return array<int, array{pregunta: string, enfoque: string, justificacion: string}>
     */
    private function parsear(string $json, int $cantidad): array
    {
        $data = json_decode($json, true);
        $preguntas = $data['preguntas'] ?? $data['questions'] ?? [];

        if (! is_array($preguntas) || empty($preguntas)) {
            throw new \RuntimeException('La IA no devolvió preguntas en el formato esperado.');
        }

        $enfoquesValidos = ['cumplimiento', 'eficacia', 'evidencia'];

        return collect($preguntas)
            ->map(function ($p) use ($enfoquesValidos) {
                $texto = trim((string) (is_array($p) ? ($p['pregunta'] ?? $p['texto'] ?? '') : $p));
                if ($texto === '') {
                    return null;
                }

                $enfoque = strtolower(trim((string) (is_array($p) ? ($p['enfoque'] ?? '') : '')));

                return [
                    'pregunta' => mb_substr($texto, 0, 1000),
                    'enfoque' => in_array($enfoque, $enfoquesValidos, true) ? $enfoque : 'cumplimiento',
                    'justificacion' => mb_substr(trim((string) (is_array($p) ? ($p['justificacion'] ?? '') : '')), 0, 500),
                ];
            })
            ->filter()
            ->take($cantidad)
            ->values()
            ->all();
    }

    private function promptSistema(int $cantidad): string
    {
        return "Eres un auditor líder de sistemas de gestión (ISO 9001, ISO 14001, ISO 45001) con "
            . "experiencia en auditorías internas. Vas a recibir un JSON con: los requisitos (cláusulas "
            . "ISO) que se van a auditar y, opcionalmente, el proceso auditado con: su documentación "
            . "(listado maestro y documentos del proceso), las preguntas de su checklist de seguimiento "
            . "diario, y su historial reciente de no conformidades — tanto novedades del registro diario "
            . "(cada una con sus hallazgos y el plan de acción de cada hallazgo) como productos no "
            . "conformes (con su análisis de causa raíz y acciones correctivas/preventivas).\n\n"
            . "Redacta exactamente {$cantidad} preguntas de auditoría en español, claras y abiertas (no de "
            . "sí/no), orientadas a verificar el cumplimiento y la eficacia de esos requisitos en el "
            . "proceso. Si el JSON trae no conformidades previas, incluye al menos una pregunta que "
            . "verifique la eficacia del plan de acción de un hallazgo concreto. Cuando sea pertinente, "
            . "apóyate en los nombres y códigos de documentos reales del JSON. Nunca inventes códigos, "
            . "documentos, cifras, fechas ni hallazgos que no estén en el JSON; si no hay contexto de "
            . "proceso, formula las preguntas solo a partir de los requisitos.\n\n"
            . "Responde ÚNICAMENTE con un objeto JSON válido con esta forma exacta:\n"
            . '{"preguntas":[{"pregunta":"...","enfoque":"cumplimiento|eficacia|evidencia","justificacion":"por qué es relevante, en una frase"}]}';
    }
}
