<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Descuento;
use App\Http\Requests\Nomina\UpdateDescuentoRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DescuentoService
{
    // =====================
    // TRAER TODOS
    // =====================
 public function getAll(array $filters = [])
{
    $query = Descuento::with([
        'empleado',
   
    ]);

    // Search por concepto o empleado
    if (!empty($filters['search'])) {
        $search = trim($filters['search']);

        $query->where(function ($q) use ($search) {
            $q->where('concepto', 'like', "%{$search}%")
              ->orWhereHas('empleado', function ($empleado) use ($search) {
                  $empleado->where('name', 'like', "%{$search}%");
              });
        });
    }

    // Filtrar por usuario
    if (!empty($filters['user_id'])) {
        $query->where('user_id', $filters['user_id']);
    }

    // Filtrar por tipo descuento
    if (!empty($filters['tipo_descuento_id'])) {
        $query->where('tipo_descuento_id', $filters['tipo_descuento_id']);
    }

    // Filtrar por estado
    if (isset($filters['status']) && $filters['status'] !== '') {
        $query->where('status', $filters['status']);
    }

    // Filtrar por fecha
    if (!empty($filters['fecha_inicio'])) {
        $query->whereDate('fecha', '>=', $filters['fecha_inicio']);
    }

    if (!empty($filters['fecha_fin'])) {
        $query->whereDate('fecha', '<=', $filters['fecha_fin']);
    }

    $query->orderByDesc('created_at');

    $perPage = $filters['per_page'] ?? 20;

    return $query->paginate($perPage);
}

    // =====================
    // TRAER UNO
    // =====================
    public function getByUuid(string $uuid): Descuento  
    {
        return Descuento::with(['empleado'])
            ->where('uuid', $uuid)       
            ->firstOrFail();
    }

    // =====================
    // CREAR
    // =====================
// SERVICE
// SERVICE
public function store(array $data): Descuento
{
    return DB::transaction(function () use ($data) {

        $data['user_id'] = $data['user_id'] ?? Auth::id();

        // Estado por defecto
        $data['status'] = $data['status'] ?? true;

        // Valor por cuota
        $data['valor_cuota'] = round(
            $data['monto'] / $data['numero_cuotas'],
            2
        );

        $inicio = Carbon::parse($data['inicio']);

        // Calcular fecha fin automática
        if ($data['frecuencia_pago'] === 'quincenal') {
            $data['fin'] = $inicio
                ->copy()
                ->addDays(($data['numero_cuotas'] - 1) * 15)
                ->format('Y-m-d');
        }

        if ($data['frecuencia_pago'] === 'mensual') {
            $data['fin'] = $inicio
                ->copy()
                ->addMonths($data['numero_cuotas'] - 1)
                ->format('Y-m-d');
        }

        $descuento = Descuento::create($data);

        Log::info('Descuento creado', [
            'uuid'            => $descuento->uuid,
            'user_id'         => $descuento->user_id,
            'monto'           => $descuento->monto,
            'numero_cuotas'   => $descuento->numero_cuotas,
            'valor_cuota'     => $descuento->valor_cuota,
            'frecuencia_pago' => $descuento->frecuencia_pago,
        ]);

        return $descuento->fresh([
            'empleado',
        ]);
    });
}
    // =====================
    // ACTUALIZAR
    // =====================
    public function update(UpdateDescuentoRequest $request, string $uuid): Descuento
    {
        return DB::transaction(function () use ($request, $uuid) {

            $descuento = $this->getByUuid($uuid);
            $data      = $request->validated();

            $monto         = $data['monto']         ?? $descuento->monto;
            $numeroCuotas  = $data['numero_cuotas'] ?? $descuento->numero_cuotas;
            $frecuencia    = $data['frecuencia_pago'] ?? $descuento->frecuencia_pago;
            $inicio        = Carbon::parse($data['inicio'] ?? $descuento->inicio);

            $data['valor_cuota'] = round($monto / $numeroCuotas, 2);

            if ($frecuencia === 'quincenal') {
                $data['fin'] = $inicio->copy()->addDays(($numeroCuotas - 1) * 15)->format('Y-m-d');
            } elseif ($frecuencia === 'mensual') {
                $data['fin'] = $inicio->copy()->addMonths($numeroCuotas - 1)->format('Y-m-d');
            }

            $descuento->update($data);

            Log::info('Descuento actualizado', [
                'uuid'        => $descuento->uuid,
                'monto'       => $descuento->monto,
                'valor_cuota' => $descuento->valor_cuota,
            ]);

            return $descuento->fresh(['empleado']);
        });
    }

    // =====================
    // ELIMINAR (soft delete)
    // =====================
    public function destroy(string $uuid): bool       
    {
        return DB::transaction(function () use ($uuid) {

            $descuento = $this->getByUuid($uuid);    

            $descuento->delete();

            Log::info('Descuento eliminado', ['uuid' => $descuento->uuid]);  

            return true;
        });
    }
}