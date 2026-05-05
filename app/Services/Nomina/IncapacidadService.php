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
            'empleado',       // trae los datos del empleado
            'revisor',        // trae los datos del revisor
            'entidadMedica',  // trae los datos de la EPS
        ])->paginate(20);
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
    return DB::transaction(function () use ($request) {

        $data = $request->validated();

        // ✅ user_id del usuario autenticado
       $data['user_id'] = Auth::id(); 
        // ✅ user_reviso_id null — se llena después en otro endpoint
        $data['user_reviso_id'] = null;

        // ✅ Si viene archivo lo guarda
        if ($request->hasFile('soporte')) {
            $data['soporte'] = $request->file('soporte')
                ->store('nomina/incapacidades', 'public');
        }

        $incapacidad = Incapacidad::create($data);

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