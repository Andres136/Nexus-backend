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
    public function getById(int $id): Nomina
    {
        return Nomina::with([
            'empleado',
            'descuento',
            'jornadaLaboral',
            'transacionalRegistro',
        ])->findOrFail($id);
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
                'id'      => $nomina->id,
                'user_id' => $nomina->user_id,
            ]);

            return $nomina;
        });
    }

    // =====================
    // ACTUALIZAR
    // =====================
    public function update(UpdateNominaRequest $request, int $id): Nomina
    {
        return DB::transaction(function () use ($request, $id) {

            $nomina = $this->getById($id);

            $nomina->update($request->validated());

            Log::info('Nómina actualizada', [
                'id'      => $nomina->id,
                'user_id' => $nomina->user_id,
            ]);

            return $nomina;
        });
    }

    // =====================
    // ELIMINAR (soft delete)
    // =====================
    public function destroy(int $id): bool
    {
        return DB::transaction(function () use ($id) {

            $nomina = $this->getById($id);

            $nomina->delete();

            Log::info('Nómina eliminada', ['id' => $nomina->id]);

            return true;
        });
    }
}