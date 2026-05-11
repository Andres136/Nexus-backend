<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Descuento;
use App\Http\Requests\Nomina\StoreDescuentoRequest;
use App\Http\Requests\Nomina\UpdateDescuentoRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DescuentoService
{
    // =====================
    // TRAER TODOS
    // =====================
    public function getAll()
    {
        return Descuento::with(['empleado'])->get();
    }

    // =====================
    // TRAER UNO
    // =====================
    public function getByUuid(string $uuid): Descuento  
    {
        return Descuento::with(['empleado'])
            ->where('uuid', $uuid)       
            ->firstOrFail();
    }

    // =====================
    // CREAR
    // =====================
    public function store(StoreDescuentoRequest $request): Descuento
    {
        return DB::transaction(function () use ($request) {

            $data = $request->validated();

            $descuento = Descuento::create($data);

            Log::info('Descuento creado', [
                'uuid'    => $descuento->uuid,  
                'user_id' => $descuento->user_id,
                'monto'   => $descuento->monto,
            ]);

            return $descuento;
        });
    }

    // =====================
    // ACTUALIZAR
    // =====================
    public function update(UpdateDescuentoRequest $request, string $uuid): Descuento  
    {
        return DB::transaction(function () use ($request, $uuid) {

            $descuento = $this->getByUuid($uuid);   

            $descuento->update($request->validated());

            Log::info('Descuento actualizado', [
                'uuid'  => $descuento->uuid,         
                'monto' => $descuento->monto,
            ]);

            return $descuento;
        });
    }

    // =====================
    // ELIMINAR (soft delete)
    // =====================
    public function destroy(string $uuid): bool       
    {
        return DB::transaction(function () use ($uuid) {

            $descuento = $this->getByUuid($uuid);    

            $descuento->delete();

            Log::info('Descuento eliminado', ['uuid' => $descuento->uuid]);  

            return true;
        });
    }
}