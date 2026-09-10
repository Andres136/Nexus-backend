<?php

namespace App\Services\Tic;

use App\Models\Tic\MantenimientoActa;
use App\Models\Tic\MantenimientoEquipos;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MantenimientoActaService
{
    public function generar(int $mantenimientoId, ?int $userId, ?string $observaciones, User $generadaPor): MantenimientoActa
    {
        return DB::transaction(function () use ($mantenimientoId, $userId, $observaciones, $generadaPor) {
            $mantenimiento = MantenimientoEquipos::with('asignacion.usuarioRecibe')
                ->lockForUpdate()
                ->findOrFail($mantenimientoId);

            $acta = MantenimientoActa::where('mantenimiento_id', $mantenimiento->id)
                ->lockForUpdate()
                ->first();

            abort_if($acta?->estado === 'firmada', 422, 'Esta acta ya fue firmada.');

            $usuario = $userId
                ? User::findOrFail($userId)
                : $mantenimiento->asignacion?->usuarioRecibe;

            abort_unless($usuario, 422, 'Debes indicar el usuario para generar el acta.');

            $acta = MantenimientoActa::updateOrCreate(
                ['mantenimiento_id' => $mantenimiento->id],
                [
                    'user_id' => $usuario->id,
                    'usuario_nombre' => $usuario->name,
                    'observaciones' => $observaciones,
                    'generada_por' => $generadaPor->id,
                    'token' => $acta->token ?? (string) Str::uuid(),
                    'estado' => 'pendiente',
                    'generada_at' => now(),
                ]
            );

            return $acta->fresh(['usuario']);
        });
    }

    public function show(int $mantenimientoId): ?MantenimientoActa
    {
        return MantenimientoActa::where('mantenimiento_id', $mantenimientoId)
            ->with('usuario')
            ->first();
    }

    public function publica(string $token): MantenimientoActa
    {
        return MantenimientoActa::with(['mantenimiento.producto', 'mantenimiento.sede', 'usuario'])
            ->where('token', $token)
            ->firstOrFail();
    }

    public function firmar(string $token, array $data, string $ip, ?string $userAgent): MantenimientoActa
    {
        return DB::transaction(function () use ($token, $data, $ip, $userAgent) {
            $acta = MantenimientoActa::where('token', $token)->lockForUpdate()->firstOrFail();
            abort_if($acta->estado === 'firmada', 422, 'Esta acta ya fue firmada.');

            $acta->update([
                'estado' => 'firmada',
                'firmada_at' => now(),
                'firma_nombre' => $data['firma_nombre'],
                'firma_imagen' => $data['firma_imagen'],
                'firma_ip' => $ip,
                'firma_user_agent' => Str::limit((string) $userAgent, 1000, ''),
            ]);

            return $acta->fresh(['mantenimiento.producto', 'usuario']);
        });
    }
}
