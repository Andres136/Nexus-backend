<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Descargo;
use App\Models\Nomina\DescargoActa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DescargoActaService
{
    public function generar(int $descargoId, ?string $observaciones, User $generadaPor): DescargoActa
    {
        return DB::transaction(function () use ($descargoId, $observaciones, $generadaPor) {
            $descargo = Descargo::with('empleado')->lockForUpdate()->findOrFail($descargoId);

            $acta = DescargoActa::where('descargo_id', $descargo->id)->lockForUpdate()->first();

            abort_if($acta?->estado === 'firmada', 422, 'Este descargo ya fue firmado.');

            $acta = DescargoActa::updateOrCreate(
                ['descargo_id' => $descargo->id],
                [
                    'user_id' => $descargo->user_id,
                    'usuario_nombre' => $descargo->empleado?->name,
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

    public function show(int $descargoId): ?DescargoActa
    {
        return DescargoActa::where('descargo_id', $descargoId)
            ->with('usuario')
            ->first();
    }

    public function publica(string $token): DescargoActa
    {
        return DescargoActa::with(['descargo.contratacion.empresa', 'usuario'])
            ->where('token', $token)
            ->firstOrFail();
    }

    public function firmar(string $token, array $data, string $ip, ?string $userAgent): DescargoActa
    {
        return DB::transaction(function () use ($token, $data, $ip, $userAgent) {
            $acta = DescargoActa::where('token', $token)->lockForUpdate()->firstOrFail();
            abort_if($acta->estado === 'firmada', 422, 'Este descargo ya fue firmado.');

            $acta->update([
                'estado' => 'firmada',
                'firmada_at' => now(),
                'firma_nombre' => $data['firma_nombre'],
                'firma_imagen' => $data['firma_imagen'],
                'firma_ip' => $ip,
                'firma_user_agent' => Str::limit((string) $userAgent, 1000, ''),
            ]);

            return $acta->fresh(['descargo.contratacion.empresa', 'usuario']);
        });
    }
}
