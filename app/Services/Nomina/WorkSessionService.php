<?php

namespace App\Services\Nomina;

use App\Models\Nomina\WorkSession;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkSessionService
{
    private const WITH = ['empleado', 'kiosko', 'jornadaLaboral'];

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
        $entrada   = $data['hora_entrada']         ?? $session?->hora_entrada;
        $salida    = $data['hola_salida']           ?? $session?->hola_salida;
        $pausaSale = $data['hora_salida_brake']     ?? $session?->hora_salida_brake;
        $pausaVuelve = $data['horara_ingreso_brake'] ?? $session?->horara_ingreso_brake;
        $pausaMinutos = $session?->minutos_pausa ?? 0;

        if ($pausaSale && $pausaVuelve) {
            $data['minutos_pausa'] = (int) Carbon::parse($pausaSale)->diffInMinutes(Carbon::parse($pausaVuelve));
            $pausaMinutos = $data['minutos_pausa'];
        }

        if ($entrada && $salida) {
            $minutos = (int) Carbon::parse($entrada)->diffInMinutes(Carbon::parse($salida));
            $data['minutos_trabajados'] = max(0, $minutos - $pausaMinutos);
        }

        return $data;
    }
}
