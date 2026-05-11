<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Incapacidad;
use App\Http\Requests\Nomina\StoreIncapacidadRequest;
use App\Http\Requests\Nomina\UpdateIncapacidadRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class IncapacidadService
{
    // =====================
    // TRAER TODAS
    // =====================
    public function getAll()
    {
        return Incapacidad::with([
            'empleado',
            'revisor',
            'entidadMedica',
        ])->paginate(20);
    }

    // =====================
    // TRAER UNA
    // =====================
    public function getByUuid(string $uuid): Incapacidad  
    {
        return Incapacidad::with([
            'empleado',
            'revisor',
            'entidadMedica',
        ])
        ->where('uuid', $uuid)   
        ->firstOrFail();
    }

    // =====================
    // CREAR
    // =====================
    public function store(StoreIncapacidadRequest $request): Incapacidad
    {
        return DB::transaction(function () use ($request) {

            $data = $request->validated();

            $data['user_id']       = Auth::id();
            $data['user_reviso_id'] = null;

            if ($request->hasFile('soporte')) {
                $data['soporte'] = $request->file('soporte')
                    ->store('nomina/incapacidades', 'public');
            }

            $incapacidad = Incapacidad::create($data);

            Log::info('Incapacidad creada', [
                'uuid'    => $incapacidad->uuid,   
                'user_id' => $incapacidad->user_id,
            ]);

            return $incapacidad;
        });
    }

    // =====================
    // ACTUALIZAR
    // =====================
    public function update(UpdateIncapacidadRequest $request, string $uuid): Incapacidad  
    {
        return DB::transaction(function () use ($request, $uuid) {

            $incapacidad = $this->getByUuid($uuid);   

            $incapacidad->update($request->validated());

            Log::info('Incapacidad actualizada', ['uuid' => $incapacidad->uuid]);  

            return $incapacidad->fresh([   
                'empleado',
                'revisor',
                'entidadMedica',
            ]);
        });
    }

    // =====================
    // ELIMINAR (soft delete)
    // =====================
    public function destroy(string $uuid): bool  
    {
        return DB::transaction(function () use ($uuid) {

            $incapacidad = $this->getByUuid($uuid);   

            $incapacidad->delete();

            Log::info('Incapacidad eliminada', ['uuid' => $incapacidad->uuid]); 

            return true;
        });
    }
}