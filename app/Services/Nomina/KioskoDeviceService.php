<?php

namespace App\Services\Nomina;

use App\Models\Nomina\KioskoDevice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KioskoDeviceService
{
    private const WITH = ['sede', 'bodega', 'tipoRegistro'];

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 10;

        return KioskoDevice::with(self::WITH)
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('ip_adres', 'like', "%{$search}%");
                });
            })
            ->when(isset($filters['sede_id']), fn($q) => $q->where('sede_id', $filters['sede_id']))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getByUuid(string $uuid): KioskoDevice
    {
        return KioskoDevice::with(self::WITH)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function create(array $data): KioskoDevice
    {
        return DB::transaction(function () use ($data) {
            $device = KioskoDevice::create($data);

            Log::info('Dispositivo kiosko creado', ['uuid' => $device->uuid, 'code' => $device->code]);

            return $device->load(self::WITH);
        });
    }

    public function update(string $uuid, array $data): KioskoDevice
    {
        return DB::transaction(function () use ($uuid, $data) {
            $device = KioskoDevice::where('uuid', $uuid)->firstOrFail();

            $device->update($data);

            Log::info('Dispositivo kiosko actualizado', ['uuid' => $device->uuid]);

            return $device->fresh(self::WITH);
        });
    }

    public function delete(string $uuid): void
    {
        DB::transaction(function () use ($uuid) {
            $device = KioskoDevice::where('uuid', $uuid)->firstOrFail();

            $device->delete();

            Log::info('Dispositivo kiosko eliminado', ['uuid' => $device->uuid]);
        });
    }
}
