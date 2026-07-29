<?php

namespace App\Services;

use App\Mail\CapacitacionActaFirmaMail;
use App\Models\Capacitacion;
use App\Models\CapacitacionActa;
use App\Models\CapacitacionActaEnvio;
use App\Models\User;
use App\RolEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CapacitacionActaService
{
    public function listar(array $filters)
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 20), 1), 100);

        return CapacitacionActa::query()
            ->with([
                'capacitacion:id,uuid,titulo,fecha_realizacion,estado',
                'elaborador:id,name,email',
            ])
            ->withCount([
                'envios',
                'envios as firmadas_count' => fn ($query) => $query->where('estado', 'firmada'),
                'envios as pendientes_count' => fn ($query) => $query->where('estado', 'pendiente'),
            ])
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($subquery) use ($search) {
                    $subquery->where('numero', 'like', "%{$search}%")
                        ->orWhere('titulo', 'like', "%{$search}%")
                        ->orWhereHas('capacitacion', fn ($capacitacion) =>
                            $capacitacion->where('titulo', 'like', "%{$search}%"));
                });
            })
            ->when(!empty($filters['estado']), function ($query) use ($filters) {
                if ($filters['estado'] === 'sin_enviar') {
                    $query->whereDoesntHave('envios');
                } elseif ($filters['estado'] === 'pendiente') {
                    $query->whereHas('envios', fn ($envio) => $envio->where('estado', 'pendiente'));
                } elseif ($filters['estado'] === 'completa') {
                    $query->whereHas('envios')
                        ->whereDoesntHave('envios', fn ($envio) => $envio->where('estado', 'pendiente'));
                }
            })
            ->when(!empty($filters['empresa_id']), fn ($query) =>
                $query->whereHas('envios', fn ($envio) => $envio->where('empresa_id', $filters['empresa_id'])))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function empresas()
    {
        return CapacitacionActaEnvio::query()
            ->whereNotNull('empresa_id')
            ->select('empresa_id', 'empresa_nombre')
            ->distinct()
            ->orderBy('empresa_nombre')
            ->get();
    }

    public function show(string $capacitacionUuid, User $user): array
    {
        $capacitacion = Capacitacion::where('uuid', $capacitacionUuid)->firstOrFail();
        $acta = CapacitacionActa::with([
            'elaborador:id,name,email',
            'envios.usuario:id,name,email',
            'envios.empresa:id,nombre',
        ])->where('capacitacion_id', $capacitacion->id)->first();

        return [
            'capacitacion' => $capacitacion,
            'acta' => $acta,
            'puede_gestionar' => $acta
                ? (int) $acta->elaborada_por === (int) $user->id
                : $this->puedeCrear($capacitacion, $user),
        ];
    }

    public function guardar(string $capacitacionUuid, array $data, User $user): CapacitacionActa
    {
        return DB::transaction(function () use ($capacitacionUuid, $data, $user) {
            $capacitacion = Capacitacion::where('uuid', $capacitacionUuid)->lockForUpdate()->firstOrFail();

            $acta = CapacitacionActa::where('capacitacion_id', $capacitacion->id)
                ->lockForUpdate()
                ->first();

            if ($acta) {
                abort_unless(
                    (int) $acta->elaborada_por === (int) $user->id,
                    403,
                    'Solo el usuario que creó el acta puede editarla.'
                );
            } else {
                abort_unless($this->puedeCrear($capacitacion, $user), 403, 'No puedes crear el acta de esta capacitación.');
                $acta = new CapacitacionActa([
                    'capacitacion_id' => $capacitacion->id,
                    'numero' => $this->siguienteNumero(),
                ]);
            }

            $acta->fill([
                ...Arr::only($data, ['titulo', 'objetivo', 'desarrollo', 'compromisos', 'conclusiones']),
                'elaborada_por' => $user->id,
            ])->save();

            return $acta->fresh(['elaborador:id,name,email', 'envios.usuario:id,name,email']);
        });
    }

    public function enviar(string $capacitacionUuid, array $userIds, User $remitente): array
    {
        $capacitacion = Capacitacion::where('uuid', $capacitacionUuid)->firstOrFail();
        $acta = CapacitacionActa::with('capacitacion')->where('capacitacion_id', $capacitacion->id)->firstOrFail();
        abort_unless(
            (int) $acta->elaborada_por === (int) $remitente->id,
            403,
            'Solo el usuario que creó el acta puede enviarla.'
        );
        $usuarios = User::with('contratacionActivaNomina.empresa:id,nombre')
            ->whereIn('id', $userIds)
            ->where('estado_id', 3)
            ->get()
            ->keyBy('id');
        $enviados = [];
        $excluidos = [];

        foreach (array_unique($userIds) as $userId) {
            $usuario = $usuarios->get($userId);
            if (!$usuario || !$usuario->email) {
                $excluidos[] = ['user_id' => $userId, 'razon' => 'Usuario inactivo, inexistente o sin correo'];
                continue;
            }

            $envio = CapacitacionActaEnvio::firstOrNew(['acta_id' => $acta->id, 'user_id' => $usuario->id]);
            if ($envio->exists && $envio->estado === 'firmada') {
                $excluidos[] = ['user_id' => $userId, 'nombre' => $usuario->name, 'razon' => 'El usuario ya firmó'];
                continue;
            }

            $envio->fill([
                'enviado_por' => $remitente->id,
                'empresa_id' => $usuario->contratacionActivaNomina?->empresa_id,
                'empresa_nombre' => $usuario->contratacionActivaNomina?->empresa?->nombre,
                'usuario_apellidos' => $usuario->apellidos,
                'numero_documento' => $usuario->contratacionActivaNomina?->numero_documento,
                'token' => Str::uuid()->toString(),
                'estado' => 'pendiente',
                'enviada_at' => now(),
            ])->save();

            $envio->setRelation('acta', $acta);
            $envio->setRelation('usuario', $usuario);
            $link = rtrim((string) config('app.frontend_url'), '/') . '/capacitacion-acta/' . $envio->token;
            Mail::to($usuario->email)->send(new CapacitacionActaFirmaMail($envio, $link));
            $enviados[] = ['user_id' => $usuario->id, 'nombre' => $usuario->name, 'email' => $usuario->email, 'link' => $link];
        }

        if ($enviados) $acta->update(['publicada_at' => now()]);

        return ['enviados' => $enviados, 'excluidos' => $excluidos];
    }

    public function publica(string $token): CapacitacionActaEnvio
    {
        return CapacitacionActaEnvio::with([
            'usuario:id,name,email',
            'empresa:id,nombre',
            'acta.capacitacion:id,uuid,titulo,fecha_realizacion,hora_inicio,hora_fin,lugar,modalidad',
            'acta.elaborador:id,name,email',
        ])->where('token', $token)->firstOrFail();
    }

    public function firmar(string $token, array $data, string $ip, ?string $userAgent): CapacitacionActaEnvio
    {
        return DB::transaction(function () use ($token, $data, $ip, $userAgent) {
            $envio = CapacitacionActaEnvio::with('usuario.contratacionActivaNomina.empresa:id,nombre')
                ->where('token', $token)
                ->lockForUpdate()
                ->firstOrFail();
            abort_if($envio->estado === 'firmada', 422, 'Esta acta ya fue firmada.');

            $envio->update([
                'estado' => 'firmada',
                'empresa_id' => $envio->empresa_id ?? $envio->usuario?->contratacionActivaNomina?->empresa_id,
                'empresa_nombre' => $envio->empresa_nombre ?? $envio->usuario?->contratacionActivaNomina?->empresa?->nombre,
                'usuario_apellidos' => $envio->usuario_apellidos ?? $envio->usuario?->apellidos,
                'numero_documento' => $envio->numero_documento ?? $envio->usuario?->contratacionActivaNomina?->numero_documento,
                'firmada_at' => now(),
                'firma_nombre' => $data['firma_nombre'],
                'firma_imagen' => $data['firma_imagen'],
                'firma_ip' => $ip,
                'firma_user_agent' => Str::limit((string) $userAgent, 1000, ''),
            ]);

            return $this->publica($token);
        });
    }

    private function puedeCrear(Capacitacion $capacitacion, User $user): bool
    {
        return (int) $capacitacion->user_id === (int) $user->id
            || (int) $user->role_id === RolEnum::ADMINISTRADOR->value;
    }

    public function datosPdf(string $capacitacionUuid, ?int $empresaId): array
    {
        $capacitacion = Capacitacion::where('uuid', $capacitacionUuid)->firstOrFail();
        $acta = CapacitacionActa::with([
            'elaborador:id,name,apellidos,email',
            'capacitacion',
            'envios' => fn ($query) => $query
                ->with(['usuario:id,name,apellidos,email', 'empresa'])
                ->when($empresaId, fn ($envio) => $envio->where('empresa_id', $empresaId))
                ->orderBy('empresa_nombre')
                ->orderBy('id'),
        ])->where('capacitacion_id', $capacitacion->id)->firstOrFail();

        $empresa = $empresaId
            ? \App\Models\Crm\empresa::findOrFail($empresaId)
            : $acta->envios->pluck('empresa')->filter()->unique('id')->sole();

        abort_if($acta->envios->isEmpty(), 422, 'No hay destinatarios de esta empresa en el acta.');

        return compact('acta', 'empresa');
    }

    private function siguienteNumero(): string
    {
        $mayor = CapacitacionActa::query()
            ->lockForUpdate()
            ->pluck('numero')
            ->filter(fn ($numero) => ctype_digit((string) $numero))
            ->map(fn ($numero) => (int) $numero)
            ->max() ?? 0;

        return str_pad((string) ($mayor + 1), 2, '0', STR_PAD_LEFT);
    }
}
