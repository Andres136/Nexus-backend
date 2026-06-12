<?php

namespace App\Services\Nomina;

use App\Models\Nomina\NominaParametroLaboral;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NominaParametroLaboralService
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 100);

        return NominaParametroLaboral::query()
            ->when(! empty($filters['anio']), fn ($query) => $query->where('anio', $filters['anio']))
            ->when(isset($filters['activo']), fn ($query) => $query->where('activo', filter_var($filters['activo'], FILTER_VALIDATE_BOOLEAN)))
            ->orderByDesc('fecha_vigencia')
            ->paginate($perPage);
    }

    public function vigente(Carbon|string|null $fecha = null): ?NominaParametroLaboral
    {
        $fecha = $fecha ? Carbon::parse($fecha)->toDateString() : now()->toDateString();

        return NominaParametroLaboral::query()
            ->where('activo', true)
            ->whereDate('fecha_vigencia', '<=', $fecha)
            ->orderByDesc('fecha_vigencia')
            ->orderByDesc('id')
            ->first();
    }

    public function store(array $data): NominaParametroLaboral
    {
        $data['auxilio_transporte'] = $data['auxilio_transporte'] ?? 0;
        $data['activo'] = $data['activo'] ?? true;

        return NominaParametroLaboral::create($data);
    }

    public function update(string $uuid, array $data): NominaParametroLaboral
    {
        $parametro = NominaParametroLaboral::where('uuid', $uuid)->firstOrFail();
        $data['auxilio_transporte'] = $data['auxilio_transporte'] ?? 0;
        $parametro->update($data);

        return $parametro->fresh();
    }
}
