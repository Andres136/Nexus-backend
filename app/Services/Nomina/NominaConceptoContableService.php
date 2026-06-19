<?php

namespace App\Services\Nomina;

use App\Models\contabilidad\Puck;
use App\Models\Nomina\NominaConceptoContable;
use App\Support\Nomina\NominaConceptoContableCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NominaConceptoContableService
{
    private const WITH = ['puck:id,numero,nombre,naturaleza,activo,permite_movimiento'];

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 50), 1), 100);

        return NominaConceptoContable::with(self::WITH)
            ->when(! empty($filters['tipo']), fn ($query) => $query->where('tipo', $filters['tipo']))
            ->when(isset($filters['activo']) && $filters['activo'] !== '', fn ($query) => $query->where('activo', filter_var($filters['activo'], FILTER_VALIDATE_BOOLEAN)))
            ->when(! empty($filters['sin_cuenta']), fn ($query) => $query->whereNull('puck_id'))
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($subquery) use ($search) {
                    $subquery->where('codigo', 'like', "%{$search}%")
                        ->orWhere('nombre', 'like', "%{$search}%")
                        ->orWhereHas('puck', fn ($puck) => $puck
                            ->where('numero', 'like', "%{$search}%")
                            ->orWhere('nombre', 'like', "%{$search}%"));
                });
            })
            ->orderByRaw("FIELD(tipo, 'devengo', 'deduccion', 'neto', 'aporte_empleador', 'provision')")
            ->orderBy('codigo')
            ->paginate($perPage);
    }

    public function getByUuid(string $uuid): NominaConceptoContable
    {
        return NominaConceptoContable::with(self::WITH)->where('uuid', $uuid)->firstOrFail();
    }

    public function update(string $uuid, array $data): NominaConceptoContable
    {
        $concepto = $this->getByUuid($uuid);
        $concepto->update($data);

        return $concepto->fresh(self::WITH);
    }

    public function sincronizarPuc(): array
    {
        $catalogo = NominaConceptoContableCatalog::byCodigo();
        $numeros = collect($catalogo)->pluck('puck_numero')->unique()->values();
        $cuentas = Puck::whereIn('numero', $numeros)
            ->where('activo', true)
            ->where('permite_movimiento', true)
            ->get()
            ->keyBy('numero');

        $enlazados = [];
        $faltantes = [];
        $sinCambios = [];

        foreach ($catalogo as $codigo => $definicion) {
            $concepto = NominaConceptoContable::where('codigo', $codigo)->first();
            $cuenta = $cuentas->get($definicion['puck_numero']);

            if (! $concepto) {
                $concepto = NominaConceptoContable::create([
                    'codigo' => $codigo,
                    'nombre' => $definicion['nombre'],
                    'tipo' => $definicion['tipo'],
                    'puck_id' => null,
                    'naturaleza' => $definicion['naturaleza'],
                    'requiere_tercero' => $definicion['requiere_tercero'] ?? true,
                    'requiere_centro_costo' => $definicion['requiere_centro_costo'] ?? false,
                    'activo' => true,
                ]);
            }

            if (! $cuenta) {
                $faltantes[] = [
                    'codigo' => $codigo,
                    'puck_numero' => $definicion['puck_numero'],
                    'motivo' => 'cuenta_puc_no_encontrada_o_no_movimiento',
                ];
                continue;
            }

            if ((int) $concepto->puck_id === (int) $cuenta->id) {
                $sinCambios[] = $codigo;
                continue;
            }

            if ($concepto->puck_id) {
                $sinCambios[] = $codigo;
                continue;
            }

            $concepto->update(['puck_id' => $cuenta->id]);
            $enlazados[] = [
                'codigo' => $codigo,
                'puck_id' => $cuenta->id,
                'puck_numero' => $cuenta->numero,
            ];
        }

        return [
            'enlazados' => $enlazados,
            'faltantes' => $faltantes,
            'sin_cambios' => $sinCambios,
        ];
    }

    public function plantillaPucFaltante(): Collection
    {
        $catalogo = collect(NominaConceptoContableCatalog::defaults())->groupBy('puck_numero');
        $numeros = $catalogo->keys();
        $cuentasValidas = Puck::whereIn('numero', $numeros)
            ->where('activo', true)
            ->where('permite_movimiento', true)
            ->pluck('numero')
            ->all();

        return $catalogo
            ->reject(fn ($conceptos, $numero) => in_array($numero, $cuentasValidas, true))
            ->map(function ($conceptos, $numero) {
                $primero = $conceptos->first();
                $nombresConceptos = $conceptos->pluck('nombre')->implode(', ');

                return [
                    'codigo' => $numero,
                    'nombre' => 'Revisar nomina - ' . $primero['nombre'],
                    'naturaleza' => $primero['naturaleza'],
                    'descripcion' => "Cuenta sugerida para conceptos de nomina: {$nombresConceptos}. Revisar con contabilidad antes de importar.",
                    'dinamica' => 'Generada como plantilla desde Configuracion de nomina.',
                ];
            })
            ->values();
    }
}
