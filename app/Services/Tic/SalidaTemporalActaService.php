<?php

namespace App\Services\Tic;

use App\Models\Tic\SalidaTemporal;
use App\Models\Tic\SalidaTemporalActa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalidaTemporalActaService
{
    public function generarAutomatico(SalidaTemporal $salidaTemporal, string $tipo, User $generadaPor): SalidaTemporalActa
    {
        return DB::transaction(function () use ($salidaTemporal, $tipo, $generadaPor) {
            $usuario = $salidaTemporal->usuario;

            $acta = SalidaTemporalActa::where('salida_temporal_id', $salidaTemporal->id)
                ->where('tipo', $tipo)
                ->lockForUpdate()
                ->first();

            abort_if($acta?->estado === 'firmada', 422, 'Esta acta ya fue firmada.');

            return SalidaTemporalActa::updateOrCreate(
                ['salida_temporal_id' => $salidaTemporal->id, 'tipo' => $tipo],
                [
                    'user_id' => $usuario->id,
                    'usuario_nombre' => $usuario->name,
                    'observaciones' => $tipo === 'retorno' ? $salidaTemporal->observaciones_retorno : $salidaTemporal->motivo,
                    'generada_por' => $generadaPor->id,
                    'token' => $acta->token ?? (string) Str::uuid(),
                    'estado' => 'pendiente',
                    'generada_at' => now(),
                ]
            )->fresh(['usuario']);
        });
    }

    public function publica(string $token): SalidaTemporalActa
    {
        return SalidaTemporalActa::with([
            'salidaTemporal.asignacion.producto',
            'salidaTemporal.asignacion.sede',
            'salidaTemporal.asignacion.empresa',
            'usuario',
        ])
            ->where('token', $token)
            ->firstOrFail();
    }

    public function firmar(string $token, array $data, string $ip, ?string $userAgent): SalidaTemporalActa
    {
        return DB::transaction(function () use ($token, $data, $ip, $userAgent) {
            $acta = SalidaTemporalActa::where('token', $token)->lockForUpdate()->firstOrFail();
            abort_if($acta->estado === 'firmada', 422, 'Esta acta ya fue firmada.');

            $acta->update([
                'estado' => 'firmada',
                'firmada_at' => now(),
                'firma_nombre' => $data['firma_nombre'],
                'firma_imagen' => $data['firma_imagen'],
                'firma_ip' => $ip,
                'firma_user_agent' => Str::limit((string) $userAgent, 1000, ''),
            ]);

            return $acta->fresh(['salidaTemporal.asignacion.producto', 'usuario']);
        });
    }
}
