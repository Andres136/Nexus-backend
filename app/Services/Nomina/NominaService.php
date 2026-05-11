<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Nomina;
use App\Http\Requests\Nomina\StoreNominaRequest;
use App\Http\Requests\Nomina\UpdateNominaRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NominaService
{
    // =====================
    // TRAER TODAS
    // =====================
    public function getAll()
    {
        return Nomina::with([
            'empleado',
            'descuento',
            'jornadaLaboral',
            'transacionalRegistro',
        ])->get();
    }

    // =====================
    // TRAER UNA
    // =====================
    public function getByUuid(string $uuid): Nomina            
    {
        return Nomina::with([
            'empleado',
            'descuento',
            'jornadaLaboral',
            'transacionalRegistro',
        ])
        ->where('uuid', $uuid)                                 
        ->firstOrFail();
    }

    // =====================
    // CREAR
    // =====================
    public function store(StoreNominaRequest $request): Nomina
    {
        return DB::transaction(function () use ($request) {

            $data = $request->validated();

            $nomina = Nomina::create($data);

            Log::info('Nómina creada', [
                'uuid'    => $nomina->uuid,                    
                'user_id' => $nomina->user_id,
            ]);

            return $nomina;
        });
    }

    // =====================
    // ACTUALIZAR
    // =====================
    public function update(UpdateNominaRequest $request, string $uuid): Nomina  
    {
        return DB::transaction(function () use ($request, $uuid) {

            $nomina = $this->getByUuid($uuid);                 

            $nomina->update($request->validated());

            Log::info('Nómina actualizada', [
                'uuid'    => $nomina->uuid,                    
                'user_id' => $nomina->user_id,
            ]);

            return $nomina->fresh([                            
                'empleado',
                'descuento',
                'jornadaLaboral',
                'transacionalRegistro',
            ]);
        });
    }

    // =====================
    // ELIMINAR (soft delete)
    // =====================
    public function destroy(string $uuid): bool                
    {
        return DB::transaction(function () use ($uuid) {

            $nomina = $this->getByUuid($uuid);                 

            $nomina->delete();

            Log::info('Nómina eliminada', ['uuid' => $nomina->uuid]);  

            return true;
        });
    }
}