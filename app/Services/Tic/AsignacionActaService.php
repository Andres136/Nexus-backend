<?php

namespace App\Services\Tic;

use App\Models\Tic\Asignaciones;
use App\Models\Tic\AsignacionActa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AsignacionActaService
{
    public function generarAutomatico(Asignaciones $asignacion, string $tipo, User $generadaPor): AsignacionActa
    {
        return DB::transaction(function () use ($asignacion, $tipo, $generadaPor) {
            $usuario = $asignacion->usuarioRecibe;

            abort_unless($usuario, 422, 'La asignación no tiene un usuario receptor para generar el acta.');

            $acta = AsignacionActa::where('asignacion_id', $asignacion->id)
                ->where('tipo', $tipo)
                ->lockForUpdate()
                ->first();

            abort_if($acta?->estado === 'firmada', 422, 'Esta acta ya fue firmada.');

            return AsignacionActa::updateOrCreate(
                ['asignacion_id' => $asignacion->id, 'tipo' => $tipo],
                [
                    'user_id' => $usuario->id,
                    'usuario_nombre' => $usuario->name,
                    'observaciones' => $asignacion->observaciones,
                    'generada_por' => $generadaPor->id,
                    'token' => $acta->token ?? (string) Str::uuid(),
                    'estado' => 'pendiente',
                    'generada_at' => now(),
                ]
            )->fresh(['usuario']);
        });
    }

    public function publica(string $token): AsignacionActa
    {
        return AsignacionActa::with(['asignacion.producto', 'asignacion.sede', 'asignacion.empresa', 'usuario'])
            ->where('token', $token)
            ->firstOrFail();
    }

    public function firmar(string $token, array $data, string $ip, ?string $userAgent): AsignacionActa
    {
        return DB::transaction(function () use ($token, $data, $ip, $userAgent) {
            $acta = AsignacionActa::where('token', $token)->lockForUpdate()->firstOrFail();
            abort_if($acta->estado === 'firmada', 422, 'Esta acta ya fue firmada.');

            $acta->update([
                'estado' => 'firmada',
                'firmada_at' => now(),
                'firma_nombre' => $data['firma_nombre'],
                'firma_imagen' => $data['firma_imagen'],
                'firma_ip' => $ip,
                'firma_user_agent' => Str::limit((string) $userAgent, 1000, ''),
            ]);

            return $acta->fresh(['asignacion.producto', 'usuario']);
        });
    }
}
