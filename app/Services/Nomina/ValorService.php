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
    public function getById(int $id): Valor
    {
        return Valor::findOrFail($id);
    }

    // =====================
    // CREAR
    // =====================
    public function store(StoreValorRequest $request): Valor
    {
        return DB::transaction(function () use ($request) {

            $valor = Valor::create($request->validated());

            Log::info('Valores creados', [
                'id'                 => $valor->id,
                'valor_hora_normal'  => $valor->valor_hora_normal,
            ]);

            return $valor;
        });
    }

    // =====================
    // ACTUALIZAR
    // =====================
    public function update(UpdateValorRequest $request, int $id): Valor
    {
        return DB::transaction(function () use ($request, $id) {

            $valor = $this->getById($id);

            $valor->update($request->validated());

            Log::info('Valores actualizados', ['id' => $valor->id]);

            return $valor;
        });
    }

    // =====================
    // ELIMINAR (soft delete)
    // =====================
    public function destroy(int $id): bool
    {
        return DB::transaction(function () use ($id) {

            $valor = $this->getById($id);

            $valor->delete();

            Log::info('Valores eliminados', ['id' => $valor->id]);

            return true;
        });
    }
}