<?php

namespace App\Services\Compras;

use App\RolEnum;
use App\Models\Compras\RequerimientoCompra;
use App\Models\Compras\RequerimientoCompraEvento;
use App\Models\Crm\bodega;
use App\Models\Crm\OrdenCompraProveedor;
use App\Models\Crm\OrdenCompraProveedorDetalle;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequerimientoCompraService
{
    private const ROLES_GESTION_COMPRAS = [
        RolEnum::ADMINISTRADOR,
        RolEnum::ADMINISTRATIVO,
        RolEnum::COMPRAS,
    ];

    private const WITH = [
        'solicitante:id,name,apellidos,email,sede_id',
        'sede:id,nombre',
        'bodega:id,nombre,sede_id',
        'ordenCompra:id,numero_orden,proveedor_id,estado_id',
        'ordenCompra.proveedor:id,nombre',
        'detalles.producto:id,name,code,description',
        'detalles.proveedorSugerido:id,nombre',
        'eventos.usuario:id,name,apellidos',
    ];

    public function listar(array $filters, User $user): LengthAwarePaginator
    {
        return RequerimientoCompra::with(self::WITH)
            ->when(
                ! $this->puedeGestionarCompras($user) || filter_var($filters['solo_mios'] ?? false, FILTER_VALIDATE_BOOLEAN),
                fn ($q) => $q->where('user_id', $user->id)
            )
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($q) use ($search) {
                    $q->where('codigo', 'like', "%{$search}%")
                        ->orWhere('observacion', 'like', "%{$search}%")
                        ->orWhereHas('solicitante', fn ($user) => $user->where('name', 'like', "%{$search}%")
                            ->orWhere('apellidos', 'like', "%{$search}%"));
                });
            })
            ->when(! empty($filters['estado']), fn ($q) => $q->where('estado', $filters['estado']))
            ->when(! empty($filters['sede_id']), fn ($q) => $q->where('sede_id', $filters['sede_id']))
            ->when(! empty($filters['bodega_id']), fn ($q) => $q->where('bodega_id', $filters['bodega_id']))
            ->when(! empty($filters['user_id']), fn ($q) => $q->where('user_id', $filters['user_id']))
            ->when(! empty($filters['orden_compra_id']), fn ($q) => $q->where('orden_compra_id', $filters['orden_compra_id']))
            ->when(! empty($filters['fecha_inicio']), fn ($q) => $q->whereDate('fecha_solicitud', '>=', $filters['fecha_inicio']))
            ->when(! empty($filters['fecha_fin']), fn ($q) => $q->whereDate('fecha_solicitud', '<=', $filters['fecha_fin']))
            ->orderByDesc('fecha_solicitud')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function obtener(string $uuid, ?User $user = null): RequerimientoCompra
    {
        $query = RequerimientoCompra::with(self::WITH)->where('uuid', $uuid);

        if ($user && ! $this->puedeGestionarCompras($user)) {
            $query->where('user_id', $user->id);
        }

        return $query->firstOrFail();
    }

    public function puedeGestionarCompras(User $user): bool
    {
        return in_array(
            (int) $user->role_id,
            array_map(fn (RolEnum $rol) => $rol->value, self::ROLES_GESTION_COMPRAS),
            true
        );
    }

    public function crear(array $data, User $user): RequerimientoCompra
    {
        if (! $user->sede_id) {
            throw ValidationException::withMessages(['sede_id' => 'Tu usuario no tiene una sede asignada.']);
        }

        return DB::transaction(function () use ($data, $user) {
            $bodegaId = $data['bodega_id'] ?? null;
            if ($bodegaId) {
                $this->validarBodegaPermitida($user, (int) $bodegaId);
            }

            $requerimiento = RequerimientoCompra::create([
                'codigo' => $this->siguienteCodigo(),
                'user_id' => $user->id,
                'sede_id' => $user->sede_id,
                'bodega_id' => $bodegaId,
                'orden_trabajo_id' => $data['orden_trabajo_id'] ?? null,
                'prioridad' => $data['prioridad'] ?? 'normal',
                'estado' => RequerimientoCompra::ESTADO_SOLICITADO,
                'fecha_requerida' => $data['fecha_requerida'] ?? null,
                'fecha_solicitud' => now(),
                'observacion' => $data['observacion'] ?? null,
            ]);

            foreach ($data['detalles'] as $detalle) {
                if (empty($detalle['producto_id']) && empty($detalle['referencia_sugerida'])) {
                    throw ValidationException::withMessages([
                        'detalles' => 'Cada detalle debe tener producto o referencia sugerida.',
                    ]);
                }

                $requerimiento->detalles()->create([
                    'producto_id' => $detalle['producto_id'] ?? null,
                    'cantidad_solicitada' => $detalle['cantidad_solicitada'],
                    'costo_estimado' => $detalle['costo_estimado'] ?? null,
                    'proveedor_sugerido_id' => $detalle['proveedor_sugerido_id'] ?? null,
                    'referencia_sugerida' => $detalle['referencia_sugerida'] ?? null,
                    'observacion' => $detalle['observacion'] ?? null,
                ]);
            }

            $this->registrarEvento($requerimiento, 'solicitado', $user, 'Requerimiento creado por solicitante.');

            return $this->obtener($requerimiento->uuid, $user);
        });
    }

    public function bodegasDisponibles(User $user): Collection
    {
        $responsabilidades = $user->responsabilidades()
            ->wherePivot('activo', true)
            ->wherePivotNotNull('bodega_id')
            ->pluck('responsabilidades_user.bodega_id')
            ->unique()
            ->values();

        if ($responsabilidades->isNotEmpty()) {
            return bodega::with('sede:id,nombre')
                ->whereIn('id', $responsabilidades)
                ->orderBy('nombre')
                ->get();
        }

        return bodega::with('sede:id,nombre')
            ->where('sede_id', $user->sede_id)
            ->orderBy('nombre')
            ->get();
    }

    public function marcarEnAnalisis(string $uuid, array $data, User $user): RequerimientoCompra
    {
        $this->exigirGestionCompras($user);

        return DB::transaction(function () use ($uuid, $data, $user) {
            $req = $this->obtener($uuid);
            $this->exigirEstado($req, [RequerimientoCompra::ESTADO_SOLICITADO]);
            $this->cambiarEstado($req, RequerimientoCompra::ESTADO_EN_ANALISIS, $user, 'en_analisis', $data['comentario'] ?? 'Compras inició análisis.');

            $req->forceFill([
                'analizado_por' => $user->id,
                'analizado_at' => now(),
            ])->save();

            return $this->obtener($uuid);
        });
    }

    public function actualizar(string $uuid, array $data, User $user): RequerimientoCompra
    {
        $this->exigirGestionCompras($user);

        return DB::transaction(function () use ($uuid, $data, $user) {
            $req = $this->obtener($uuid);
            $this->exigirEstado($req, [
                RequerimientoCompra::ESTADO_SOLICITADO,
                RequerimientoCompra::ESTADO_EN_ANALISIS,
                RequerimientoCompra::ESTADO_APROBADO,
            ]);

            $bodegaId = $data['bodega_id'] ?? null;
            if ($bodegaId) {
                $this->validarBodegaPermitida($req->solicitante, (int) $bodegaId);
            }

            $req->update([
                'bodega_id' => $bodegaId,
                'orden_trabajo_id' => $data['orden_trabajo_id'] ?? null,
                'prioridad' => $data['prioridad'] ?? $req->prioridad,
                'fecha_requerida' => $data['fecha_requerida'] ?? null,
                'observacion' => $data['observacion'] ?? null,
            ]);

            $req->detalles()->delete();
            foreach ($data['detalles'] as $detalle) {
                if (empty($detalle['producto_id']) && empty($detalle['referencia_sugerida'])) {
                    throw ValidationException::withMessages([
                        'detalles' => 'Cada detalle debe tener producto o referencia sugerida.',
                    ]);
                }

                $req->detalles()->create([
                    'producto_id' => $detalle['producto_id'] ?? null,
                    'cantidad_solicitada' => $detalle['cantidad_solicitada'],
                    'cantidad_aprobada' => $detalle['cantidad_aprobada'] ?? null,
                    'costo_estimado' => $detalle['costo_estimado'] ?? null,
                    'proveedor_sugerido_id' => $detalle['proveedor_sugerido_id'] ?? null,
                    'referencia_sugerida' => $detalle['referencia_sugerida'] ?? null,
                    'observacion' => $detalle['observacion'] ?? null,
                ]);
            }

            $this->registrarEvento($req, 'actualizado', $user, 'Requerimiento editado por compras.');

            return $this->obtener($uuid);
        });
    }

    public function aprobar(string $uuid, array $data, User $user): RequerimientoCompra
    {
        $this->exigirGestionCompras($user);

        return DB::transaction(function () use ($uuid, $data, $user) {
            $req = $this->obtener($uuid);
            $this->exigirEstado($req, [RequerimientoCompra::ESTADO_SOLICITADO, RequerimientoCompra::ESTADO_EN_ANALISIS]);

            $aprobaciones = collect($data['detalles'] ?? [])->keyBy('id');
            foreach ($req->detalles as $detalle) {
                $cantidad = $aprobaciones->has($detalle->id)
                    ? (float) $aprobaciones->get($detalle->id)['cantidad_aprobada']
                    : (float) $detalle->cantidad_solicitada;
                $detalle->update(['cantidad_aprobada' => $cantidad]);
            }

            if ($req->detalles()->sum('cantidad_aprobada') <= 0) {
                throw ValidationException::withMessages(['detalles' => 'Aprueba al menos una cantidad mayor a cero.']);
            }

            $req->forceFill([
                'analizado_por' => $req->analizado_por ?? $user->id,
                'analizado_at' => $req->analizado_at ?? now(),
            ])->save();
            $this->cambiarEstado($req, RequerimientoCompra::ESTADO_APROBADO, $user, 'aprobado', $data['comentario'] ?? 'Requerimiento aprobado para compra.');

            return $this->obtener($uuid);
        });
    }

    public function rechazar(string $uuid, array $data, User $user): RequerimientoCompra
    {
        $this->exigirGestionCompras($user);

        return DB::transaction(function () use ($uuid, $data, $user) {
            $req = $this->obtener($uuid);
            $this->exigirEstado($req, [
                RequerimientoCompra::ESTADO_SOLICITADO,
                RequerimientoCompra::ESTADO_EN_ANALISIS,
                RequerimientoCompra::ESTADO_APROBADO,
            ]);

            $req->forceFill([
                'rechazado_por' => $user->id,
                'rechazado_at' => now(),
                'motivo_rechazo' => $data['motivo'],
            ])->save();
            $this->cambiarEstado($req, RequerimientoCompra::ESTADO_RECHAZADO, $user, 'rechazado', $data['motivo']);

            return $this->obtener($uuid);
        });
    }

    public function cancelar(string $uuid, array $data, User $user): RequerimientoCompra
    {
        return DB::transaction(function () use ($uuid, $data, $user) {
            $req = $this->obtener($uuid, $user);
            $this->exigirEstado($req, [
                RequerimientoCompra::ESTADO_SOLICITADO,
                RequerimientoCompra::ESTADO_EN_ANALISIS,
                RequerimientoCompra::ESTADO_APROBADO,
            ]);
            $this->cambiarEstado($req, RequerimientoCompra::ESTADO_CANCELADO, $user, 'cancelado', $data['motivo'] ?? 'Requerimiento cancelado.');

            return $this->obtener($uuid);
        });
    }

    public function generarOrdenCompra(string $uuid, array $data, User $user): RequerimientoCompra
    {
        $this->exigirGestionCompras($user);

        return DB::transaction(function () use ($uuid, $data, $user) {
            $req = $this->obtener($uuid);
            $this->exigirEstado($req, [RequerimientoCompra::ESTADO_APROBADO]);

            $bodegaId = $data['bodega_id'] ?? $req->bodega_id;
            if ($bodegaId) {
                $this->validarBodegaPermitida($req->solicitante, (int) $bodegaId);
            }

            $orden = OrdenCompraProveedor::create([
                'proveedor_id' => $data['proveedor_id'],
                'fecha' => now(),
                'numero_orden' => $this->siguienteNumeroOrdenCompra(),
                'estado_id' => 1,
                'usuario_id' => $user->id,
                'observaciones' => $data['observaciones'] ?? "Generada desde requerimiento {$req->codigo}",
                'empresa_id' => $data['empresa_id'],
                'bodega_id' => $bodegaId,
                'sede_id' => $req->sede_id,
                'fecha_entrega' => $data['fecha_entrega'] ?? $req->fecha_requerida,
            ]);

            $item = 1;
            foreach ($req->detalles as $detalle) {
                $cantidad = (float) ($detalle->cantidad_aprobada ?? $detalle->cantidad_solicitada);
                if ($cantidad <= 0) {
                    continue;
                }

                OrdenCompraProveedorDetalle::create([
                    'orden_id' => $orden->id,
                    'producto_id' => $detalle->producto_id,
                    'code' => $detalle->producto?->code ?? $detalle->referencia_sugerida,
                    'proveedor_id' => $detalle->proveedor_sugerido_id ?: $data['proveedor_id'],
                    'descripcion' => $detalle->producto?->description
                        ?? $detalle->producto?->name
                        ?? $detalle->referencia_sugerida
                        ?? 'Producto solicitado',
                    'cantidad_solicitada' => $cantidad,
                    'cantidad_entregada' => 0,
                    'item' => $item++,
                ]);

                $detalle->update(['cantidad_comprada' => $cantidad]);
            }

            $req->forceFill([
                'orden_compra_id' => $orden->id,
                'generado_oc_por' => $user->id,
                'generado_oc_at' => now(),
            ])->save();
            $this->cambiarEstado($req, RequerimientoCompra::ESTADO_OC_GENERADA, $user, 'oc_generada', "Orden de compra {$orden->numero_orden} generada.");

            return $this->obtener($uuid);
        });
    }

    public function validarBodegaPermitida(User $user, int $bodegaId): void
    {
        $permitida = $this->bodegasDisponibles($user)->contains('id', $bodegaId);

        if (! $permitida) {
            throw ValidationException::withMessages([
                'bodega_id' => 'La bodega seleccionada no pertenece a tu sede o a tus responsabilidades activas.',
            ]);
        }
    }

    private function registrarEvento(RequerimientoCompra $req, string $tipo, ?User $user, ?string $comentario = null, array $metadata = []): void
    {
        RequerimientoCompraEvento::create([
            'requerimiento_compra_id' => $req->id,
            'user_id' => $user?->id,
            'tipo_evento' => $tipo,
            'estado_anterior' => null,
            'estado_nuevo' => $req->estado,
            'comentario' => $comentario,
            'metadata' => $metadata ?: null,
        ]);
    }

    private function cambiarEstado(RequerimientoCompra $req, string $estado, User $user, string $tipoEvento, ?string $comentario): void
    {
        $anterior = $req->estado;
        $req->forceFill(['estado' => $estado])->save();

        RequerimientoCompraEvento::create([
            'requerimiento_compra_id' => $req->id,
            'user_id' => $user->id,
            'tipo_evento' => $tipoEvento,
            'estado_anterior' => $anterior,
            'estado_nuevo' => $estado,
            'comentario' => $comentario,
        ]);
    }

    private function exigirEstado(RequerimientoCompra $req, array $permitidos): void
    {
        if (! in_array($req->estado, $permitidos, true)) {
            throw ValidationException::withMessages([
                'estado' => "El requerimiento {$req->codigo} no permite esta acción desde estado {$req->estado}.",
            ]);
        }
    }

    private function exigirGestionCompras(User $user): void
    {
        if (! $this->puedeGestionarCompras($user)) {
            throw ValidationException::withMessages([
                'rol' => 'Solo administracion o compras puede gestionar requerimientos de otros usuarios.',
            ]);
        }
    }

    private function siguienteCodigo(): string
    {
        $next = ((int) RequerimientoCompra::withTrashed()->max('id')) + 1;

        return 'REQ-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    private function siguienteNumeroOrdenCompra(): string
    {
        $next = ((int) OrdenCompraProveedor::max('id')) + 1;

        return 'OC-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
