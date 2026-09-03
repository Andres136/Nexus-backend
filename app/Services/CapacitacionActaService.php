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
    public function __construct(private readonly TareaService $tareaService) {}

    public function listar(array $filters, User $user)
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 20), 1), 100);

        return CapacitacionActa::query()
            // Un acta solo la ve quien la elaboró; los administradores ven todas.
            ->when(!$this->esAdmin($user), fn ($query) => $query->where('elaborada_por', $user->id))
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

        // El acta solo la puede ver quien la elaboró (o un administrador). Para
        // delegarla a otra persona se usa "reasignar", que la pasa a su nombre.
        if ($acta) {
            abort_unless(
                (int) $acta->elaborada_por === (int) $user->id || $this->esAdmin($user),
                403,
                'Solo el usuario que elaboró el acta puede consultarla.'
            );
        }

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

            // Si ya existe acta, la capacitación se da por realizada (salvo que esté cancelada).
            if ($capacitacion->estado !== 'cancelada' && $capacitacion->estado !== 'realizada') {
                $capacitacion->update(['estado' => 'realizada']);
            }

            return $acta->fresh(['elaborador:id,name,email', 'envios.usuario:id,name,email']);
        });
    }

    /**
     * Transfiere quién gestiona el acta (elaborada_por) a otro usuario. Solo
     * quien ya puede gestionarla (o un administrador) puede reasignarla. Si
     * el acta todavía no existe, se crea vacía ya asignada a ese usuario —
     * así se puede delegar antes de empezar a llenarla.
     */
    public function reasignar(string $capacitacionUuid, int $nuevoElaboradorId, User $user): CapacitacionActa
    {
        return DB::transaction(function () use ($capacitacionUuid, $nuevoElaboradorId, $user) {
            $capacitacion = Capacitacion::where('uuid', $capacitacionUuid)->lockForUpdate()->firstOrFail();

            $acta = CapacitacionActa::where('capacitacion_id', $capacitacion->id)
                ->lockForUpdate()
                ->first();

            $esAdmin = (int) $user->role_id === RolEnum::ADMINISTRADOR->value;
            $puedeGestionar = $acta
                ? (int) $acta->elaborada_por === (int) $user->id
                : $this->puedeCrear($capacitacion, $user);

            abort_unless($esAdmin || $puedeGestionar, 403, 'No puedes reasignar el acta de esta capacitación.');

            if (!$acta) {
                $acta = new CapacitacionActa([
                    'capacitacion_id' => $capacitacion->id,
                    'numero' => $this->siguienteNumero(),
                    // titulo/desarrollo son NOT NULL en la tabla; se reemplazan en
                    // cuanto el responsable asignado guarde el contenido real.
                    'titulo' => "Acta de capacitación — {$capacitacion->titulo}",
                    'desarrollo' => '',
                ]);
            }

            $acta->elaborada_por = $nuevoElaboradorId;
            $acta->save();

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
        $primeraPublicacion = $acta->publicada_at === null;
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

        if ($enviados) {
            $acta->update(['publicada_at' => now()]);

            if ($primeraPublicacion) {
                $this->crearTareasDeCompromisos($acta, $remitente);
            }
        }

        return ['enviados' => $enviados, 'excluidos' => $excluidos];
    }

    private function crearTareasDeCompromisos(CapacitacionActa $acta, User $remitente): void
    {
        $responsableIds = collect($acta->compromisos ?? [])
            ->pluck('responsable')
            ->filter()
            ->unique();

        if ($responsableIds->isEmpty()) {
            return;
        }

        $responsables = User::whereIn('id', $responsableIds)->get()->keyBy('id');

        foreach ($acta->compromisos as $compromiso) {
            $responsable = $responsables->get($compromiso['responsable'] ?? null);

            if (!$responsable || !$responsable->departamento_id) {
                continue;
            }

            $this->tareaService->crear([
                'nombre' => "Compromiso: {$acta->titulo}",
                'descripcion' => $compromiso['descripcion'],
                'fecha_fin' => $compromiso['fecha'] ?: null,
                'departamento_id' => $responsable->departamento_id,
                'user_id' => $responsable->id,
            ], $remitente->id);
        }
    }

    public function publica(string $token): CapacitacionActaEnvio
    {
        $envio = CapacitacionActaEnvio::with([
            'usuario:id,name,email',
            'empresa:id,nombre',
            'acta.capacitacion:id,uuid,titulo,fecha_realizacion,hora_inicio,hora_fin,lugar,modalidad',
            'acta.elaborador:id,name,email',
        ])->where('token', $token)->firstOrFail();

        if ($envio->acta) {
            $envio->acta->setAttribute('compromisos', $this->compromisosConNombre($envio->acta));
        }

        return $envio;
    }

    // compromisos guarda el user_id del responsable (para poder generar
    // tareas al publicar), así que para mostrarlo hay que resolver el
    // nombre aparte — el frontend de edición del acta sí necesita el id
    // crudo (para preseleccionar el <select>), por eso esto solo se aplica
    // en las vistas de solo lectura (PDF y acta pública), no en show().
    private function compromisosConNombre(CapacitacionActa $acta): array
    {
        $responsables = User::whereIn('id', collect($acta->compromisos ?? [])->pluck('responsable')->filter()->unique())
            ->get(['id', 'name', 'apellidos'])
            ->keyBy('id');

        return collect($acta->compromisos ?? [])->map(function ($compromiso) use ($responsables) {
            $responsable = $responsables->get($compromiso['responsable'] ?? null);

            return [
                ...$compromiso,
                'responsable_nombre' => $responsable ? trim("{$responsable->name} {$responsable->apellidos}") : null,
            ];
        })->all();
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
            || $this->esAdmin($user);
    }

    private function esAdmin(User $user): bool
    {
        return (int) $user->role_id === RolEnum::ADMINISTRADOR->value;
    }

    public function datosPdf(string $capacitacionUuid, ?int $empresaId, User $user): array
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

        abort_unless(
            (int) $acta->elaborada_por === (int) $user->id || $this->esAdmin($user),
            403,
            'Solo el usuario que elaboró el acta puede descargarla.'
        );

        $empresa = $empresaId
            ? \App\Models\Crm\empresa::findOrFail($empresaId)
            : $acta->envios->pluck('empresa')->filter()->unique('id')->sole();

        abort_if($acta->envios->isEmpty(), 422, 'No hay destinatarios de esta empresa en el acta.');

        $acta->setAttribute('compromisos', $this->compromisosConNombre($acta));

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
