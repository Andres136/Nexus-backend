<?php

namespace App\Services\Nomina;

use App\Models\Nomina\WorkSession;
use App\Models\Nomina\JornadaLaboral;
use App\Http\Requests\Nomina\StoreWorkSessionRequest;
use App\Http\Requests\Nomina\UpdateWorkSessionRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkSessionService
{
    // =====================
    // TRAER TODAS
    // =====================
    public function getAll()
    {
        return WorkSession::with([
            'empleado',
            'kiosko',
            'jornadaLaboral',
        ])->get();
    }

    // =====================
    // TRAER UNA
    // =====================
    public function getById(int $id): WorkSession
    {
        return WorkSession::with([
            'empleado',
            'kiosko',
            'jornadaLaboral',
        ])->findOrFail($id);
    }

    // =====================
    // CREAR
    // =====================
    public function store(StoreWorkSessionRequest $request): WorkSession
    {
        return DB::transaction(function () use ($request) {

            $data = $request->validated();

            // Calcula minutos trabajados si vienen las dos horas
            if (isset($data['hora_entrada']) && isset($data['hola_salida'])) {
                $entrada = Carbon::parse($data['hora_entrada']);
                $salida  = Carbon::parse($data['hola_salida']);
                $data['minutos_trabajados'] = $salida->diffInMinutes($entrada);
            }

            // Calcula tardanza comparando con la jornada
            if (isset($data['hora_entrada']) && isset($data['horario_laboral_id'])) {
                $jornada    = JornadaLaboral::findOrFail($data['horario_laboral_id']);
                $entrada    = Carbon::parse($data['hora_entrada']);
                $horaInicio = Carbon::parse('08:00:00'); // hora inicio jornada
                $data['minutos_tardanza'] = max(0, $horaInicio->diffInMinutes($entrada, false));
            }

            $session = WorkSession::create($data);

            Log::info('WorkSession creada', [
                'id'      => $session->id,
                'user_id' => $session->users_id,
                'dia'     => $session->registro_diario,
            ]);

            return $session;
        });
    }

    // =====================
    // ACTUALIZAR
    // =====================
    public function update(UpdateWorkSessionRequest $request, int $id): WorkSession
    {
        return DB::transaction(function () use ($request, $id) {

            $session = $this->getById($id);
            $data    = $request->validated();

            // Recalcula pausa si vienen las dos horas del brake
            if (isset($data['hora_salida_brake']) && isset($data['horara_ingreso_brake'])) {
                $salida  = Carbon::parse($data['hora_salida_brake']);
                $regreso = Carbon::parse($data['horara_ingreso_brake']);
                $data['minutos_pausa'] = $regreso->diffInMinutes($salida);
            }

            // Recalcula minutos trabajados si viene la salida
            if (isset($data['hola_salida'])) {
                $entrada = Carbon::parse($session->hora_entrada);
                $salida  = Carbon::parse($data['hola_salida']);
                $pausa   = $session->minutos_pausa ?? 0;
                $data['minutos_trabajados'] = $salida->diffInMinutes($entrada) - $pausa;
            }

            $session->update($data);

            Log::info('WorkSession actualizada', [
                'id'  => $session->id,
                'dia' => $session->registro_diario,
            ]);

            return $session;
        });
    }

    // =====================
    // ELIMINAR (soft delete)
    // =====================
    public function destroy(int $id): bool
    {
        return DB::transaction(function () use ($id) {

            $session = $this->getById($id);
            $session->delete();

            Log::info('WorkSession eliminada', ['id' => $session->id]);

            return true;
        });
    }
}