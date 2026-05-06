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
    public function getById(int $id): Descuento
    {
        return Descuento::with(['empleado'])
            ->findOrFail($id);
    }

    // =====================
    // CREAR
    // =====================
    public function store(StoreDescuentoRequest $request): Descuento
    {
        return DB::transaction(function () use ($request) {

            $data = $request->validated();

            // user_id del usuario autenticado
            $data['user_id'] = Auth::id();

            $descuento = Descuento::create($data);

            Log::info('Descuento creado', [
                'id'      => $descuento->id,
                'user_id' => $descuento->user_id,
                'monto'   => $descuento->monto,
            ]);

            return $descuento;
        });
    }

    // =====================
    // ACTUALIZAR
    // =====================
    public function update(UpdateDescuentoRequest $request, int $id): Descuento
    {
        return DB::transaction(function () use ($request, $id) {

            $descuento = $this->getById($id);

            $descuento->update($request->validated());

            Log::info('Descuento actualizado', [
                'id'    => $descuento->id,
                'monto' => $descuento->monto,
            ]);

            return $descuento;
        });
    }

    // =====================
    // ELIMINAR (soft delete)
    // =====================
    public function destroy(int $id): bool
    {
        return DB::transaction(function () use ($id) {

            $descuento = $this->getById($id);

            $descuento->delete();

            Log::info('Descuento eliminado', ['id' => $descuento->id]);

            return true;
        });
    }
}