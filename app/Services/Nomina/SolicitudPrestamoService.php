<?php

namespace App\Services\Nomina;

use App\Models\Nomina\SolicitudPrestamo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;

class SolicitudPrestamoService
{
    private const WITH = ['empleado:id,name,email', 'gestionadoPor:id,name', 'descuento:id,uuid'];

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        return SolicitudPrestamo::with(self::WITH)
            ->when(!empty($filters['search']), fn ($q) => $q->whereHas(
                'empleado',
                fn ($empleado) => $empleado->where('name', 'like', "%{$filters['search']}%")
            ))
            ->when(!empty($filters['user_id']), fn ($q) => $q->where('user_id', $filters['user_id']))
            ->when(isset($filters['status']) && $filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getByUuid(string $uuid): SolicitudPrestamo
    {
        return SolicitudPrestamo::with(self::WITH)->where('uuid', $uuid)->firstOrFail();
    }

    public function store(array $data, ?int $userId = null): SolicitudPrestamo
    {
        $solicitud = SolicitudPrestamo::create([
            'user_id' => $userId ?? Auth::id(),
            'monto_solicitado' => $data['monto_solicitado'],
            'numero_cuotas_solicitadas' => $data['numero_cuotas_solicitadas'] ?? null,
            'frecuencia_pago_solicitada' => $data['frecuencia_pago_solicitada'],
            'motivo' => $data['motivo'] ?? null,
            'status' => 'pendiente',
        ]);

        Log::info('Solicitud de prestamo creada', [
            'uuid' => $solicitud->uuid,
            'user_id' => $solicitud->user_id,
        ]);

        return $solicitud->fresh(self::WITH);
    }

    public function aprobar(string $uuid, array $data, DescuentoService $descuentoService): SolicitudPrestamo
    {
        return DB::transaction(function () use ($uuid, $data, $descuentoService) {
            $solicitud = $this->getByUuid($uuid);

            if ($solicitud->status !== 'pendiente') {
                throw new LogicException("La solicitud ya fue {$solicitud->status}.");
            }

            $montoAprobado = round((float) ($data['monto_aprobado'] ?? $solicitud->monto_solicitado), 2);
            $tasa = round((float) ($data['tasa_interes_porcentaje'] ?? 0), 2);
            $valorInteres = round($montoAprobado * ($tasa / 100), 2);
            $total = round($montoAprobado + $valorInteres, 2);
            $cuotas = (int) $data['numero_cuotas_aprobadas'];
            $valorCuota = round($total / $cuotas, 2);

            $descuento = $descuentoService->store([
                'user_id' => $solicitud->user_id,
                'monto' => $total,
                'inicio' => $data['inicio_descuento'],
                'status' => true,
                'concepto_descuento' => 'Prestamo empleado',
                'numero_cuotas' => $cuotas,
                'frecuencia_pago' => $data['frecuencia_pago_aprobada'],
            ]);

            $solicitud->update([
                'descuento_id' => $descuento->id,
                'gestionado_por_id' => Auth::id(),
                'status' => 'aprobada',
                'monto_aprobado' => $montoAprobado,
                'tasa_interes_porcentaje' => $tasa,
                'valor_interes' => $valorInteres,
                'total_a_descontar' => $total,
                'numero_cuotas_aprobadas' => $cuotas,
                'valor_cuota_aprobada' => $valorCuota,
                'frecuencia_pago_aprobada' => $data['frecuencia_pago_aprobada'],
                'inicio_descuento' => $data['inicio_descuento'],
                'observacion_nomina' => $data['observacion_nomina'] ?? null,
                'fecha_gestion' => now(),
            ]);

            Log::info('Solicitud de prestamo aprobada', [
                'uuid' => $solicitud->uuid,
                'descuento_id' => $descuento->id,
            ]);

            return $solicitud->fresh(self::WITH);
        });
    }

    public function rechazar(string $uuid, ?string $observacion = null): SolicitudPrestamo
    {
        return DB::transaction(function () use ($uuid, $observacion) {
            $solicitud = $this->getByUuid($uuid);

            if ($solicitud->status !== 'pendiente') {
                throw new LogicException("La solicitud ya fue {$solicitud->status}.");
            }

            $solicitud->update([
                'gestionado_por_id' => Auth::id(),
                'status' => 'rechazada',
                'observacion_nomina' => $observacion,
                'fecha_gestion' => now(),
            ]);

            return $solicitud->fresh(self::WITH);
        });
    }
}
