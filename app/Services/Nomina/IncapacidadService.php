<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Incapacidad;
use App\Http\Requests\Nomina\StoreIncapacidadRequest;
use App\Http\Requests\Nomina\UpdateIncapacidadRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IncapacidadService
{
    // =====================
    // TRAER TODAS
    // =====================
    public function getAll()
    {
        return Incapacidad::with([
            'empleado',       // trae los datos del empleado
            'revisor',        // trae los datos del revisor
            'entidadMedica',  // trae los datos de la EPS
        ])->get();
    }

    // =====================
    // TRAER UNA
    // =====================
    public function getById(int $id): Incapacidad
    {
        return Incapacidad::with([
            'empleado',
            'revisor',
            'entidadMedica',
        ])->findOrFail($id);
        // findOrFail: si no existe lanza 404 automáticamente
    }

    // =====================
    // CREAR
    // =====================
    public function store(StoreIncapacidadRequest $request): Incapacidad
    {
        // DB::transaction: si algo falla, revierte TODO
        // así nunca quedan datos a medias en la BD
        return DB::transaction(function () use ($request) {

            $incapacidad = Incapacidad::create(
                $request->validated()
                // validated(): solo toma los campos que pasaron las reglas del Request
            );

            Log::info('Incapacidad creada', [
                'id'      => $incapacidad->id,
                'user_id' => $incapacidad->user_id,
            ]);

            return $incapacidad;
        });
    }

    // =====================
    // ACTUALIZAR
    // =====================
    public function update(UpdateIncapacidadRequest $request, int $id): Incapacidad
    {
        return DB::transaction(function () use ($request, $id) {

            $incapacidad = $this->getById($id);

            $incapacidad->update($request->validated());

            Log::info('Incapacidad actualizada', ['id' => $incapacidad->id]);

            return $incapacidad;
        });
    }

    // =====================
    // ELIMINAR (soft delete)
    // =====================
    public function destroy(int $id): bool
    {
        return DB::transaction(function () use ($id) {

            $incapacidad = $this->getById($id);

            $incapacidad->delete();
            // delete() con softDeletes no borra el registro
            // solo pone fecha en deleted_at

            Log::info('Incapacidad eliminada', ['id' => $incapacidad->id]);

            return true;
        });
    }
}