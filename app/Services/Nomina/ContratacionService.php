<?php

namespace App\Services\Nomina;

use App\EstadoEnum;
use App\Models\Nomina\Contratacion;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;

class ContratacionService
{
    private const WITH = [
        'tipoContrato:id,nombre,codigo',
        'usuario:id,name,apellidos,email,sede_id',
        'usuario.sede:id,nombre',
        'empresa:id,nombre',
        'parametroLaboral:id,uuid,anio,fecha_vigencia,salario_minimo,auxilio_transporte',
        'eps:id,nombre,nit',
        'arl:id,nombre,nit',
        'fondoPensiones:id,nombre,nit',
        'cajaPenciones:id,nombre,nit',
        'fondoCesantias:id,nombre,nit',
    ];

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 10), 1), 100);
        $periodoInicio = $filters['periodo_inicio'] ?? null;
        $periodoFin = $filters['periodo_fin'] ?? null;

        $query = Contratacion::with(self::WITH)
            ->whereHas('usuario', fn ($q) => $q->where('estado_id', EstadoEnum::ACTIVO->value))
            ->when($periodoInicio && $periodoFin, fn ($query) => $query->with([
                'nominas' => fn ($nominas) => $nominas
                    ->with('liquidador:id,name,email')
                    ->where('liquidada', true)
                    ->operativas()
                    ->whereDate('periodo_inicio', '<=', $periodoFin)
                    ->whereDate('periodo_fin', '>=', $periodoInicio)
                    ->latest('fecha_liquidacion'),
            ]))
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($q) use ($search) {
                    $q->where('uuid', 'like', "%{$search}%")
                      ->orWhere('base_salario', 'like', "%{$search}%")
                      ->orWhere('no_salarial', 'like', "%{$search}%")
                      ->orWhereHas('usuario', fn($u) => $u->where('name', 'like', "%{$search}%")
                                                           ->orWhere('apellidos', 'like', "%{$search}%")
                                                           ->orWhere('email', 'like', "%{$search}%"))
                      ->orWhereHas('tipoContrato', fn($t) => $t->where('nombre', 'like', "%{$search}%")
                                                               ->orWhere('codigo', 'like', "%{$search}%"))
                      ->orWhereHas('eps', fn($e) => $e->where('nombre', 'like', "%{$search}%"));
                });
            })
            ->when(!empty($filters['fecha_inicio']), fn($q) =>
                $q->whereDate('inicio_contratacion', '>=', $filters['fecha_inicio']))
            ->when(!empty($filters['fecha_fin']), fn($q) =>
                $q->whereDate('inicio_contratacion', '<=', $filters['fecha_fin']))
            ->when(!empty($filters['user_id']), fn($q) =>
                $q->where('users_id', $filters['user_id']))
            ->when(!empty($filters['empresa_id']), fn($q) =>
                $q->where('empresa_id', $filters['empresa_id']))
            ->when(isset($filters['status']), fn($q) =>
                $q->where('status', $filters['status']))
            ->orderByDesc('created_at');

        $paginator = $query->paginate($perPage);

        if ($periodoInicio && $periodoFin) {
            $paginator->getCollection()->each(function (Contratacion $contratacion) {
                $contratacion->setRelation('nomina_periodo', $contratacion->nominas->first());
                $contratacion->unsetRelation('nominas');
            });
        }

        return $paginator;
    }

    public function getByUuid(string $uuid): Contratacion
    {
        return Contratacion::with(self::WITH)
            ->whereHas('usuario', fn ($q) => $q->where('estado_id', EstadoEnum::ACTIVO->value))
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function getEmpleadosOptions(array $filters = []): Collection
    {
        return User::select(
            'users.id',
            'users.name',
            'users.apellidos',
            'users.sede_id',
            DB::raw('(SELECT c.numero_documento FROM contrataciones c WHERE c.users_id = users.id ORDER BY c.id DESC LIMIT 1) as numero_documento')
        )
            ->where('users.estado_id', EstadoEnum::ACTIVO->value)
            ->when(!empty($filters['con_contrato']), fn ($query) =>
                $query->whereHas('contratacionActivaNomina'))
            ->when(!empty($filters['sede_id']), fn ($query) =>
                $query->where('users.sede_id', $filters['sede_id']))
            ->orderBy('users.name')
            ->get();
    }

    public function create(array $data): Contratacion
    {
        return DB::transaction(function () use ($data) {
            if ($this->tieneContratoActivo((int) $data['users_id'])) {
                throw new LogicException('Este empleado ya tiene un contrato activo. Debes inactivar o finalizar el contrato actual antes de registrar uno nuevo.');
            }

            $data = $this->normalizarEconomicos($data, true);
            $contratacion = Contratacion::create($data);

            Log::info('Contratación creada', ['uuid' => $contratacion->uuid, 'users_id' => $contratacion->users_id]);

            KioskoDeviceService::clearBootstrapCache();

            return $contratacion->load(self::WITH);
        });
    }

    private function tieneContratoActivo(int $userId, ?string $excludeUuid = null): bool
    {
        return Contratacion::where('users_id', $userId)
            ->where('status', true)
            ->when($excludeUuid, fn($q) => $q->where('uuid', '!=', $excludeUuid))
            ->exists();
    }

    public function update(string $uuid, array $data): Contratacion
    {
        return DB::transaction(function () use ($uuid, $data) {
            $contratacion = Contratacion::where('uuid', $uuid)->firstOrFail();

            $data = $this->normalizarEconomicos($data);
            $contratacion->update($data);

            Log::info('Contratación actualizada', ['uuid' => $contratacion->uuid]);

            KioskoDeviceService::clearBootstrapCache();

            return $contratacion->fresh(self::WITH);
        });
    }

    public function cambiarEstado(string $uuid, bool $status): Contratacion
    {
        return DB::transaction(function () use ($uuid, $status) {
            $contratacion = Contratacion::where('uuid', $uuid)->firstOrFail();

            $contratacion->update(['status' => $status ? 1 : 0]);

            Log::info('Estado de contratación actualizado', [
                'uuid' => $contratacion->uuid,
                'status' => $contratacion->status,
            ]);

            KioskoDeviceService::clearBootstrapCache();

            return $contratacion->fresh(self::WITH);
        });
    }

    public function delete(string $uuid): void
    {
        DB::transaction(function () use ($uuid) {
            $contratacion = Contratacion::where('uuid', $uuid)->firstOrFail();

            $contratacion->delete();

            Log::info('Contratación eliminada', ['uuid' => $contratacion->uuid]);

            KioskoDeviceService::clearBootstrapCache();
        });
    }

    private function normalizarEconomicos(array $data, bool $crear = false): array
    {
        if ($crear) {
            $data['tipo_salario'] ??= 'personalizado';
            $data['auxilio_transporte'] = $data['auxilio_transporte'] ?? 0;
            $data['no_salarial'] = $data['no_salarial'] ?? 0;
            $data['dias_vacaciones_iniciales'] = $data['dias_vacaciones_iniciales'] ?? 0;
            $data['salario_integral'] = $data['salario_integral'] ?? false;
            $data['aplica_salud'] = $data['aplica_salud'] ?? true;
            $data['aplica_pension'] = $data['aplica_pension'] ?? true;
            $data['aplica_arl'] = $data['aplica_arl'] ?? true;
            $data['aplica_sena'] = $data['aplica_sena'] ?? true;
            $data['aplica_icbf'] = $data['aplica_icbf'] ?? true;
            $data['aplica_caja_compensacion'] = $data['aplica_caja_compensacion'] ?? true;
        }

        if (($data['tipo_salario'] ?? null) && $data['tipo_salario'] !== 'salario_minimo') {
            $data['parametro_laboral_id'] = null;
        }

        return $data;
    }
}
