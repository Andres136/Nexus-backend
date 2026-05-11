<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Valor;
use App\Http\Requests\Nomina\StoreValorRequest;
use App\Http\Requests\Nomina\UpdateValorRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ValorService
{
    // =====================
    // TRAER TODOS
    // =====================
    public function getAll()
    {
        return Valor::all();
    }

    // =====================
    // TRAER UNO
    // =====================
    public function getByUuid(string $uuid): Valor                 // ← getById(int $id) → getByUuid(string $uuid)
    {
        return Valor::where('uuid', $uuid)                         // ← findOrFail($id) → where + firstOrFail
            ->firstOrFail();
    }

    // =====================
    // CREAR
    // =====================
    public function store(StoreValorRequest $request): Valor
    {
        return DB::transaction(function () use ($request) {

            $valor = Valor::create($request->validated());

            Log::info('Valores creados', [
                'uuid'               => $valor->uuid,              // ← 'id' → 'uuid'
                'valor_hora_normal'  => $valor->valor_hora_normal,
            ]);

            return $valor;
        });
    }

    // =====================
    // ACTUALIZAR
    // =====================
    public function update(UpdateValorRequest $request, string $uuid): Valor  // ← int $id → string $uuid
    {
        return DB::transaction(function () use ($request, $uuid) {

            $valor = $this->getByUuid($uuid);                      // ← getById($id) → getByUuid($uuid)

            $valor->update($request->validated());

            Log::info('Valores actualizados', ['uuid' => $valor->uuid]);  // ← 'id' → 'uuid'

            return $valor->fresh();                                // ← agregado fresh() para datos actualizados
        });
    }

    // =====================
    // ELIMINAR (soft delete)
    // =====================
    public function destroy(string $uuid): bool                    // ← int $id → string $uuid
    {
        return DB::transaction(function () use ($uuid) {

            $valor = $this->getByUuid($uuid);                      // ← getById($id) → getByUuid($uuid)

            $valor->delete();

            Log::info('Valores eliminados', ['uuid' => $valor->uuid]);  // ← 'id' → 'uuid'

            return true;
        });
    }
}