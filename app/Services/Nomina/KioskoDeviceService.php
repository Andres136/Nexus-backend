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

    public function getByUuid(string $uuid): KioskoDevice         
    {
        return KioskoDevice::with(['sede', 'bodega', 'tipoRegistro'])
            ->where('uuid', $uuid)                                
            ->firstOrFail();
    }

    public function create(array $data): KioskoDevice
    {
        return KioskoDevice::create($data);
    }

    public function update(string $uuid, array $data): KioskoDevice  
    {
        $device = KioskoDevice::where('uuid', $uuid)              
            ->firstOrFail();

        $device->update($data);

        return $device->fresh(['sede', 'bodega', 'tipoRegistro']); 
    }

    public function delete(string $uuid): void                    
    {
        $device = KioskoDevice::where('uuid', $uuid)              
            ->firstOrFail();

        $device->delete();
    }
}