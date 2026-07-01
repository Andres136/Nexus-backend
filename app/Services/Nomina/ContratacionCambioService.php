<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Contratacion;
use App\Models\Nomina\ContratacionCambio;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use LogicException;

class ContratacionCambioService
{
    private const WITH = [
        'contratacion:id,uuid,cargo,users_id',
        'contratacion.usuario:id,name,apellidos,email',
        'empleado:id,name,apellidos,email',
        'registradoPor:id,name,email',
    ];

    private const CAMPOS_CONTRATO = [
        'id_contrato',
        'empresa_id',
        'cargo',
        'base_salario',
        'no_salarial',
        'auxilio_transporte',
        'pago_frecuencia',
        'inicio_contratacion',
        'fin_contrato',
        'eps_id',
        'arl_id',
        'fondo_pensiones_id',
        'caja_penciones_id',
        'fondo_cesantias_id',
        'salario_integral',
        'aplica_salud',
        'aplica_pension',
        'aplica_arl',
        'aplica_sena',
        'aplica_icbf',
        'aplica_caja_compensacion',
    ];

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 10), 1), 100);

        return ContratacionCambio::with(self::WITH)
            ->when(! empty($filters['contratacion_id']), fn ($q) => $q->where('contratacion_id', $filters['contratacion_id']))
            ->when(! empty($filters['user_id']), fn ($q) => $q->where('user_id', $filters['user_id']))
            ->when(! empty($filters['tipo_cambio']), fn ($q) => $q->where('tipo_cambio', $filters['tipo_cambio']))
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($q) use ($search) {
                    $q->where('motivo', 'like', "%{$search}%")
                        ->orWhere('observaciones', 'like', "%{$search}%")
                        ->orWhereHas('empleado', fn ($empleado) => $empleado
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('apellidos', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('fecha_cambio')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getByUuid(string $uuid): ContratacionCambio
    {
        return ContratacionCambio::with(self::WITH)->where('uuid', $uuid)->firstOrFail();
    }

    public function create(array $data): ContratacionCambio
    {
        return DB::transaction(function () use ($data) {
            $contratacion = Contratacion::lockForUpdate()->findOrFail($data['contratacion_id']);
            $datosNuevos = collect($data['datos_nuevos'] ?? [])
                ->only(self::CAMPOS_CONTRATO)
                ->reject(fn ($value) => $value === '')
                ->all();

            if (empty($datosNuevos)) {
                throw new LogicException('Debes indicar al menos un dato valido para modificar el contrato.');
            }

            $datosAnteriores = collect(self::CAMPOS_CONTRATO)
                ->mapWithKeys(fn ($campo) => [$campo => $contratacion->{$campo}])
                ->only(array_keys($datosNuevos))
                ->all();

            $contratacion->update($datosNuevos);

            return ContratacionCambio::create([
                'contratacion_id' => $contratacion->id,
                'user_id' => $contratacion->users_id,
                'tipo_cambio' => $data['tipo_cambio'],
                'fecha_cambio' => $data['fecha_cambio'],
                'motivo' => $data['motivo'],
                'observaciones' => $data['observaciones'] ?? null,
                'datos_anteriores' => $datosAnteriores,
                'datos_nuevos' => $datosNuevos,
                'registrado_por' => Auth::id(),
            ])->load(self::WITH);
        });
    }
}
