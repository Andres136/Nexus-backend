<?php

namespace App\Services\Nomina;

use App\Models\Nomina\TransacionalRegistro;
use App\Models\Nomina\WorkSession;
use App\Http\Requests\Nomina\StoreTransacionalRegistroRequest;
use App\Http\Requests\Nomina\UpdateTransacionalRegistroRequest;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TransacionalRegistroService
{
    private const WITH = ['empleado', 'kioskoDevice', 'tipoMarcacion'];

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return TransacionalRegistro::with(self::WITH)
            ->when(!empty($filters['users_id']), fn($q) => $q->where('users_id', $filters['users_id']))
            ->when(!empty($filters['fecha']), fn($q) => $q->whereDate('marked_ad', $filters['fecha']))
            ->orderByDesc('marked_ad')
            ->paginate($perPage);
    }

    public function getByUuid(string $uuid): TransacionalRegistro
    {
        return TransacionalRegistro::with(self::WITH)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function getByUser(int $userId): Collection
    {
        return TransacionalRegistro::with(['tipoMarcacion', 'kioskoDevice'])
            ->where('users_id', $userId)
            ->orderByDesc('marked_ad')
            ->get();
    }

    public function store(StoreTransacionalRegistroRequest $request): TransacionalRegistro
    {
        return DB::transaction(function () use ($request) {
            $data               = $request->validated();
            $horarioLaboralId   = $data['horario_laboral_id'];
            unset($data['horario_laboral_id']);

            if ($request->hasFile('foto_referencia')) {
                $data['foto_referencia'] = $request->file('foto_referencia')
                    ->store('nomina/marcaciones', 'public');
            }

            $registro = TransacionalRegistro::create($data);

            Log::info('Marcación registrada', [
                'uuid'    => $registro->uuid,
                'user_id' => $registro->users_id,
                'hora'    => $registro->marked_ad,
            ]);

            $this->syncWorkSession($registro, $horarioLaboralId);

            return $registro->load(self::WITH);
        });
    }

    public function update(UpdateTransacionalRegistroRequest $request, string $uuid): TransacionalRegistro
    {
        return DB::transaction(function () use ($request, $uuid) {
            $registro = $this->getByUuid($uuid);
            $data     = $request->validated();

            if ($request->hasFile('foto_referencia')) {
                if ($registro->foto_referencia) {
                    Storage::disk('public')->delete($registro->foto_referencia);
                }
                $data['foto_referencia'] = $request->file('foto_referencia')
                    ->store('nomina/marcaciones', 'public');
            }

            $registro->update($data);

            Log::info('Marcación actualizada', ['uuid' => $registro->uuid]);

            return $registro->fresh(self::WITH);
        });
    }

    public function delete(string $uuid): void
    {
        DB::transaction(function () use ($uuid) {
            $registro = $this->getByUuid($uuid);

            if ($registro->foto_referencia) {
                Storage::disk('public')->delete($registro->foto_referencia);
            }

            $registro->delete();

            Log::info('Marcación eliminada', ['uuid' => $registro->uuid]);
        });
    }

    private function syncWorkSession(TransacionalRegistro $registro, int $horarioLaboralId): void
    {
        $registro->load('tipoMarcacion');
        $tipo   = strtolower($registro->tipoMarcacion->name ?? '');
        $fecha  = Carbon::parse($registro->marked_ad)->toDateString();
        $cuando = $registro->marked_ad;

        $session = WorkSession::firstOrNew([
            'user_id'        => $registro->users_id,
            'registro_diario' => $fecha,
        ]);

        if (!$session->exists) {
            $session->kiosko_id          = $registro->kiosk_device_id;
            $session->horario_laboral_id  = $horarioLaboralId;
            $session->minutos_trabajados  = 0;
            $session->minutos_pausa       = 0;
            $session->minutos_tardanza    = 0;
        }

        if (str_contains($tipo, 'entrada')) {
            $session->hora_entrada     = $cuando;
            $horaRef                   = Carbon::parse($fecha . ' 08:00:00');
            $session->minutos_tardanza = max(0, (int) $horaRef->diffInMinutes($cuando, false));

        } elseif (str_contains($tipo, 'salida') && str_contains($tipo, 'almuerzo')) {
            $session->hora_salida_almuerzo = $cuando;

        } elseif (str_contains($tipo, 'almuerzo') || str_contains($tipo, 'regreso almuerzo')) {
            $session->hora_ingreso_almuerzo = $cuando;

        } elseif (str_contains($tipo, 'pausa') && (str_contains($tipo, 'inicio') || str_contains($tipo, 'salida'))) {
            $session->hora_salida_brake = $cuando;

        } elseif (str_contains($tipo, 'pausa') && (str_contains($tipo, 'fin') || str_contains($tipo, 'regreso'))) {
            $session->hora_ingreso_brake = $cuando;
            if ($session->hora_salida_brake) {
                $session->minutos_pausa = (int) Carbon::parse($session->hora_salida_brake)
                    ->diffInMinutes($cuando);
            }

        } elseif (str_contains($tipo, 'salida')) {
            $session->hora_salida = $cuando;
            if ($session->hora_entrada) {
                $minutos = (int) Carbon::parse($session->hora_entrada)->diffInMinutes($cuando);
                $session->minutos_trabajados = max(0, $minutos - ($session->minutos_pausa ?? 0));
            }
        }

        $session->save();

        Log::info('WorkSession sincronizada', [
            'user_id'  => $registro->users_id,
            'fecha'    => $fecha,
            'tipo'     => $tipo,
        ]);
    }
}
