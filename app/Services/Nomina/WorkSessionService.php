<?php

namespace App\Services\Nomina;

use App\Models\Nomina\WorkSession;
use App\Models\Nomina\JornadaLaboral;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkSessionService
{
    private const WITH = ['empleado', 'kiosko', 'jornadaLaboral'];
    private const PAUSA_PERMITIDA_MINUTOS = 15;
    private const ALMUERZO_PERMITIDO_MINUTOS = 60;

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return WorkSession::with(self::WITH)
            ->when(!empty($filters['user_id']), fn($q) => $q->where('user_id', $filters['user_id']))
            ->when(!empty($filters['fecha']), fn($q) => $q->whereDate('registro_diario', $filters['fecha']))
            ->when(!empty($filters['fecha_inicio']), fn($q) => $q->whereDate('registro_diario', '>=', $filters['fecha_inicio']))
            ->when(!empty($filters['fecha_fin']), fn($q) => $q->whereDate('registro_diario', '<=', $filters['fecha_fin']))
            ->orderByDesc('registro_diario')
            ->paginate($perPage);
    }

    public function getByUuid(string $uuid): WorkSession
    {
        return WorkSession::with(self::WITH)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function store(array $data): WorkSession
    {
        return DB::transaction(function () use ($data) {
            $data = $this->calcularMinutos($data);

            $session = WorkSession::create($data);

            Log::info('WorkSession creada', [
                'uuid' => $session->uuid,
                'dia'  => $session->registro_diario,
            ]);

            return $session->load(self::WITH);
        });
    }

    public function update(string $uuid, array $data): WorkSession
    {
        return DB::transaction(function () use ($uuid, $data) {
            $session = $this->getByUuid($uuid);

            $data = $this->calcularMinutos($data, $session);

            $session->update($data);

            Log::info('WorkSession actualizada', [
                'uuid' => $session->uuid,
                'dia'  => $session->registro_diario,
            ]);

            return $session->fresh(self::WITH);
        });
    }

    public function destroy(string $uuid): void
    {
        DB::transaction(function () use ($uuid) {
            $session = $this->getByUuid($uuid);
            $session->delete();

            Log::info('WorkSession eliminada', ['uuid' => $session->uuid]);
        });
    }

    private function calcularMinutos(array $data, ?WorkSession $session = null): array
    {
        $jornada = $this->resolverJornada($data, $session);
        $entrada   = $data['hora_entrada']         ?? $session?->hora_entrada;
        $salida    = $data['hora_salida']           ?? $session?->hora_salida;
        $pausaSale = $data['hora_salida_brake']     ?? $session?->hora_salida_brake;
        $pausaVuelve = $data['hora_ingreso_brake'] ?? $session?->hora_ingreso_brake;
        $almuerzoSale = $data['hora_salida_almuerzo'] ?? $session?->hora_salida_almuerzo;
        $almuerzoVuelve = $data['hora_ingreso_almuerzo'] ?? $session?->hora_ingreso_almuerzo;
        $pausaMinutos = $session?->minutos_pausa ?? 0;
        $tardanzaMinutos = 0;
        $pausaPermitida = $jornada?->duracion_pausa_minutos ?? self::PAUSA_PERMITIDA_MINUTOS;
        $almuerzoPermitido = $jornada?->duracion_almuerzo_minutos ?? self::ALMUERZO_PERMITIDO_MINUTOS;

        if ($entrada) {
            $horaEntradaProgramada = $jornada?->hora_entrada ?? '07:00:00';
            $entradaReal = Carbon::parse($entrada);
            $entradaBase = Carbon::parse($entradaReal->toDateString() . ' ' . $horaEntradaProgramada);
            $tardanzaMinutos += $entradaReal->greaterThan($entradaBase)
                ? (int) $entradaBase->diffInMinutes($entradaReal)
                : 0;
        }

        if ($pausaSale && $pausaVuelve) {
            $data['minutos_pausa'] = (int) Carbon::parse($pausaSale)->diffInMinutes(Carbon::parse($pausaVuelve));
            $pausaMinutos = $data['minutos_pausa'];
            $tardanzaMinutos += max(0, $pausaMinutos - $pausaPermitida);
        }

        if ($almuerzoSale && $almuerzoVuelve) {
            $almuerzoMinutos = (int) Carbon::parse($almuerzoSale)->diffInMinutes(Carbon::parse($almuerzoVuelve));
            $tardanzaMinutos += max(0, $almuerzoMinutos - $almuerzoPermitido);
        }

        $data['minutos_tardanza'] = $tardanzaMinutos;

        if ($entrada && $salida) {
            $minutos = (int) Carbon::parse($entrada)->diffInMinutes(Carbon::parse($salida));
            $data['minutos_trabajados'] = max(0, $minutos - $pausaMinutos);
        }

        return $data;
    }

    private function resolverJornada(array $data, ?WorkSession $session = null): ?JornadaLaboral
    {
        $jornadaId = $data['horario_laboral_id'] ?? $session?->horario_laboral_id;

        if (!$jornadaId) {
            return null;
        }

        if ($session?->relationLoaded('jornadaLaboral') && (int) $session->horario_laboral_id === (int) $jornadaId) {
            return $session->jornadaLaboral;
        }

        return JornadaLaboral::find($jornadaId);
    }
}
