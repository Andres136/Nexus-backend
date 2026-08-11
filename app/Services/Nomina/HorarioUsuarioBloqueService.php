<?php

namespace App\Services\Nomina;

use App\Models\Nomina\HorarioUsuarioBloque;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class HorarioUsuarioBloqueService
{
    public function porUsuario(int $userId): Collection
    {
        return HorarioUsuarioBloque::where('user_id', $userId)
            ->orderBy('dia_semana')
            ->orderBy('orden')
            ->get();
    }

    public function todos(): Collection
    {
        return HorarioUsuarioBloque::with('empleado:id,name,apellidos')
            ->where('status', true)
            ->orderBy('user_id')
            ->orderBy('dia_semana')
            ->orderBy('orden')
            ->get();
    }

    public function cambiarEstado(int $userId, bool $status): Collection
    {
        HorarioUsuarioBloque::where('user_id', $userId)->update(['status' => $status]);

        return $this->porUsuario($userId);
    }

    public function guardarSemana(int $userId, array $bloques): Collection
    {
        return DB::transaction(function () use ($userId, $bloques) {
            HorarioUsuarioBloque::where('user_id', $userId)->delete();

            foreach ($bloques as $bloque) {
                HorarioUsuarioBloque::create([
                    'user_id' => $userId,
                    'dia_semana' => $bloque['dia_semana'],
                    'hora_inicio' => $bloque['hora_inicio'],
                    'hora_fin' => $bloque['hora_fin'],
                    'orden' => $bloque['orden'] ?? 0,
                    'status' => $bloque['status'] ?? true,
                ]);
            }

            return $this->porUsuario($userId);
        });
    }
}
