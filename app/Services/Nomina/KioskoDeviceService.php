<?php

namespace App\Services\Nomina;

use App\Models\Nomina\KioskoDevice;
use Illuminate\Support\Collection;

class KioskoDeviceService
{
    public function getAll(): Collection
    {
        return KioskoDevice::with(['sede', 'bodega', 'tipoRegistro'])->get();
    }

    public function getById(int $id): KioskoDevice
    {
        return KioskoDevice::with(['sede', 'bodega', 'tipoRegistro'])->findOrFail($id);
    }

    public function create(array $data): KioskoDevice
    {
        return KioskoDevice::create($data);
    }

    public function update(int $id, array $data): KioskoDevice
    {
        $device = KioskoDevice::findOrFail($id);
        $device->update($data);
        return $device;
    }

    public function delete(int $id): void
    {
        $device = KioskoDevice::findOrFail($id);
        $device->delete();
    }
}
