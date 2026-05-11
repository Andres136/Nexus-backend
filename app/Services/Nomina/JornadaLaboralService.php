<?php

namespace App\Services\Nomina;

use App\Models\Nomina\JornadaLaboral;
use App\Http\Requests\Nomina\StoreJornadaLaboralRequest;
use App\Http\Requests\Nomina\UpdateJornadaLaboralRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JornadaLaboralService
{
    // =====================
    // TRAER TODAS
    // =====================
    public function getAll()
    {
        return JornadaLaboral::all();
    }

    // =====================
    // TRAER UNA
    // =====================
    public function getByUuid(string $uuid): JornadaLaboral  
    {
        return JornadaLaboral::where('uuid', $uuid)          
        
            ->firstOrFail();
    }

    // =====================
    // CREAR
    // =====================
    public function store(StoreJornadaLaboralRequest $request): JornadaLaboral
    {
        return DB::transaction(function () use ($request) {

            $jornada = JornadaLaboral::create($request->validated());

            Log::info('Jornada laboral creada', [
                'uuid'            => $jornada->uuid,         
                'horas_semanales' => $jornada->horas_semanales,
            ]);

            return $jornada;
        });
    }

    // =====================
    // ACTUALIZAR
    // =====================
    public function update(UpdateJornadaLaboralRequest $request, string $uuid): JornadaLaboral  
    {
        return DB::transaction(function () use ($request, $uuid) {

            $jornada = $this->getByUuid($uuid);              

            $jornada->update($request->validated());

            Log::info('Jornada laboral actualizada', ['uuid' => $jornada->uuid]);  

            return $jornada->fresh();                        
        });
    }

    // =====================
    // ELIMINAR (soft delete)
    // =====================
    public function destroy(string $uuid): bool              
    {
        return DB::transaction(function () use ($uuid) {

            $jornada = $this->getByUuid($uuid);              

            $jornada->delete();

            Log::info('Jornada laboral eliminada', ['uuid' => $jornada->uuid]);  

            return true;
        });
    }
}