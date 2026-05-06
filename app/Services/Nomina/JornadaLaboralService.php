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
    public function getById(int $id): JornadaLaboral
    {
        return JornadaLaboral::findOrFail($id);
    }

    // =====================
    // CREAR
    // =====================
    public function store(StoreJornadaLaboralRequest $request): JornadaLaboral
    {
        return DB::transaction(function () use ($request) {

            $jornada = JornadaLaboral::create(
                $request->validated()
            );

            Log::info('Jornada laboral creada', [
                'id'              => $jornada->id,
                'horas_semanales' => $jornada->horas_semanales,
            ]);

            return $jornada;
        });
    }

    // =====================
    // ACTUALIZAR
    // =====================
    public function update(UpdateJornadaLaboralRequest $request, int $id): JornadaLaboral
    {
        return DB::transaction(function () use ($request, $id) {

            $jornada = $this->getById($id);

            $jornada->update($request->validated());

            Log::info('Jornada laboral actualizada', ['id' => $jornada->id]);

            return $jornada;
        });
    }

    // =====================
    // ELIMINAR (soft delete)
    // =====================
    public function destroy(int $id): bool
    {
        return DB::transaction(function () use ($id) {

            $jornada = $this->getById($id);

            $jornada->delete();

            Log::info('Jornada laboral eliminada', ['id' => $jornada->id]);

            return true;
        });
    }
}