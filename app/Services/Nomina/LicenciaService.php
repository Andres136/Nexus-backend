<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Licencia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LicenciaService
{
    private const WITH = ['empleado:id,name,email,sede_id', 'empleado.sede:id,nombre', 'autorizador:id,name'];

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $paginator = Licencia::with(self::WITH)
            ->when(!empty($filters['search']), fn ($q) => $q->whereHas(
                'empleado',
                fn ($e) => $e->where('name', 'like', "%{$filters['search']}%")
            ))
            ->when(!empty($filters['user_id']), fn ($q) => $q->where('user_id', $filters['user_id']))
            ->when(!empty($filters['sede_id']), fn ($q) => $q->whereHas('empleado', fn ($empleado) => $empleado->where('sede_id', $filters['sede_id'])))
            ->when(isset($filters['status']) && $filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['fecha_desde']), fn ($q) => $q->whereDate('fin', '>=', $filters['fecha_desde']))
            ->when(!empty($filters['fecha_hasta']), fn ($q) => $q->whereDate('inicio', '<=', $filters['fecha_hasta']))
            ->orderByDesc('inicio')
            ->paginate($filters['per_page'] ?? 15);

        $paginator->getCollection()->transform(fn ($item) => $this->withSoporteUrl($item));

        return $paginator;
    }

    public function getByUuid(string $uuid): Licencia
    {
        return $this->withSoporteUrl($this->findByUuid($uuid));
    }

    private function findByUuid(string $uuid): Licencia
    {
        return Licencia::with(self::WITH)->where('uuid', $uuid)->firstOrFail();
    }

    private function withSoporteUrl(Licencia $licencia): Licencia
    {
        $licencia->soporte_url = $licencia->soporte
            ? asset('storage/' . $licencia->soporte)
            : null;
        return $licencia;
    }

    public function store(array $data): Licencia
    {
        return DB::transaction(function () use ($data) {
            $soportePath = null;
            if (!empty($data['soporte'])) {
                $soportePath = $data['soporte']->store('licencias/soportes', 'public');
            }

            $licencia = Licencia::create([
                'tipo'            => $data['tipo'],
                'inicio'          => $data['inicio'],
                'fin'             => $data['fin'],
                'dias_calendario' => $data['dias_calendario'],
                'motivo'          => $data['motivo'] ?? null,
                'soporte'         => $soportePath,
                'status'          => 'pendiente',
                'user_id'         => $data['user_id'],
            ]);

            Log::info('Licencia registrada', [
                'uuid'    => $licencia->uuid,
                'user_id' => $licencia->user_id,
                'tipo'        => $licencia->tipo,
                'inicio'      => $licencia->inicio,
                'fin'         => $licencia->fin,
            ]);

            return $licencia->load(self::WITH);
        });
    }

    public function aprobar(string $uuid, ?string $observacion = null): Licencia
    {
        return DB::transaction(function () use ($uuid, $observacion) {
            $licencia = $this->findByUuid($uuid);

            if ($licencia->status !== 'pendiente') {
                throw new \LogicException("La licencia ya fue {$licencia->status}.");
            }

            $licencia->update([
                'status'        => 'aprobada',
                'autorizador_id' => Auth::id(),
                'observacion'   => $observacion,
                'fecha_gestion' => now(),
            ]);

            Log::info('Licencia aprobada', [
                'uuid'          => $licencia->uuid,
                'autorizador_id' => Auth::id(),
            ]);

            return $licencia->fresh(self::WITH);
        });
    }

    public function rechazar(string $uuid, ?string $observacion = null): Licencia
    {
        return DB::transaction(function () use ($uuid, $observacion) {
            $licencia = $this->findByUuid($uuid);

            if ($licencia->status !== 'pendiente') {
                throw new \LogicException("La licencia ya fue {$licencia->status}.");
            }

            $licencia->update([
                'status'        => 'rechazada',
                'autorizador_id' => Auth::id(),
                'observacion'   => $observacion,
                'fecha_gestion' => now(),
            ]);

            Log::info('Licencia rechazada', [
                'uuid'          => $licencia->uuid,
                'autorizador_id' => Auth::id(),
            ]);

            return $licencia->fresh(self::WITH);
        });
    }

    public function destroy(string $uuid): void
    {
        DB::transaction(function () use ($uuid) {
            $licencia = $this->findByUuid($uuid);

            if ($licencia->soporte && Storage::disk('public')->exists($licencia->soporte)) {
                Storage::disk('public')->delete($licencia->soporte);
            }

            $licencia->delete();

            Log::info('Licencia eliminada', ['uuid' => $licencia->uuid]);
        });
    }
}
