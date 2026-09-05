<?php

namespace App\Services\Nomina;

use App\Models\Nomina\HoraExtra;
use App\Models\Nomina\KioskoDevice;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HoraExtraService
{
    private const WITH = [
        'empleado:id,name,apellidos,email,sede_id',
        'sede:id,nombre',
        'kiosko:id,uuid,name,code,sede_id',
        'solicitante:id,name,apellidos,email',
        'supervisor:id,name,apellidos,email',
    ];

    private function aplicarFiltros(Builder $query, array $filters): Builder
    {
        return $query
            ->when(!empty($filters['user_id']),  fn($q) => $q->where('user_id', $filters['user_id']))
            ->when(!empty($filters['mine']), fn($q) => $q->where('solicitado_por', Auth::id()))
            ->when(!empty($filters['sede_id']),  fn($q) => $q->where('sede_id', $filters['sede_id']))
            ->when(!empty($filters['kiosko_device_id']), fn($q) => $q->where('kiosko_device_id', $filters['kiosko_device_id']))
            ->when(!empty($filters['status']),   fn($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['tipo']),     fn($q) => $q->where('tipo', $filters['tipo']))
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);

                $query->where(function ($q) use ($search) {
                    $q->whereHas('empleado', fn ($empleado) =>
                        $empleado->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                    )
                    ->orWhereHas('solicitante', fn ($solicitante) =>
                        $solicitante->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                    )
                    ->orWhereHas('kiosko', fn ($kiosko) =>
                        $kiosko->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                    );
                });
            })
            ->when(!empty($filters['fecha_desde']), fn($q) => $q->whereDate('fecha', '>=', $filters['fecha_desde']))
            ->when(!empty($filters['fecha_hasta']), fn($q) => $q->whereDate('fecha', '<=', $filters['fecha_hasta']));
    }

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return $this->aplicarFiltros(HoraExtra::with(self::WITH), $filters)
            // Pendientes primero (son las que hay que gestionar), luego por fecha.
            ->orderByRaw("CASE WHEN status = 'pendiente' THEN 0 WHEN status = 'rechazada' THEN 1 ELSE 2 END")
            ->orderByDesc('fecha')
            ->paginate($perPage);
    }

    /**
     * Aprueba en lote todas las horas extras pendientes que cumplan los filtros.
     * El filtro de status se fuerza a 'pendiente': solo esas se pueden aprobar.
     */
    public function aprobarTodas(array $filters, ?string $observacion = null): int
    {
        return DB::transaction(function () use ($filters, $observacion) {
            $filters['status'] = 'pendiente';
            $ids = $this->aplicarFiltros(HoraExtra::query(), $filters)
                ->lockForUpdate()
                ->pluck('id');

            if ($ids->isEmpty()) {
                return 0;
            }

            HoraExtra::whereIn('id', $ids)->update([
                'status' => 'aprobada',
                'autorizado_por' => Auth::id(),
                'fecha_gestion' => now(),
                'observacion_gestion' => $observacion,
            ]);

            Log::info('Horas extras aprobadas en lote', [
                'cantidad' => $ids->count(),
                'autorizado_por' => Auth::id(),
            ]);

            return $ids->count();
        });
    }

    /**
     * Horas extras que cumplen los filtros aplicados (cualquier estado, o el que
     * venga en $filters['status']), para exportar a Excel con información completa.
     */
    public function getParaExportar(array $filters): Collection
    {
        return $this->aplicarFiltros(
            HoraExtra::with(array_merge(self::WITH, [
                'empleado.contratacionActivaNomina' => fn ($q) => $q->select(
                    'contrataciones.id',
                    'contrataciones.users_id',
                    'contrataciones.numero_documento',
                    'contrataciones.cargo',
                ),
            ])),
            $filters
        )
            ->orderBy('fecha')
            ->orderBy('user_id')
            ->get();
    }

    /**
     * Minutos de cada hora extra que REALMENTE respalda la marcación de asistencia
     * de ese día (lo que la nómina reconocería). Misma idea que
     * NominaService: reconocido = min(aprobado, exceso real del día), repartido
     * entre las horas extra aprobadas del mismo día. Devuelve [hora_extra_id => minutos];
     * las que no están aprobadas quedan en null (aún no aplican).
     *
     * @param  Collection<int, HoraExtra>  $horasExtras
     * @return array<int, int|null>
     */
    public function minutosReconocidosPorAsistencia(Collection $horasExtras): array
    {
        $reconocidos = [];
        $jornadaActiva = \App\Models\Nomina\JornadaLaboral::where('status', true)
            ->orderByDesc('updated_at')->first();

        $porUsuarioFecha = $horasExtras->groupBy(fn (HoraExtra $h) => $h->user_id.'|'.optional($h->fecha)->toDateString());

        foreach ($porUsuarioFecha as $clave => $grupo) {
            [$userId, $fecha] = explode('|', $clave);

            $aprobadas = $grupo->where('status', 'aprobada')->sortBy('id')->values();
            foreach ($grupo->whereNotIn('status', ['aprobada']) as $h) {
                $reconocidos[$h->id] = null;
            }

            if ($aprobadas->isEmpty() || ! $fecha) {
                continue;
            }

            $sesiones = \App\Models\Nomina\WorkSession::with('jornadaLaboral')
                ->where('user_id', $userId)
                ->whereDate('registro_diario', $fecha)
                ->get();

            $trabajados = (int) $sesiones->sum('minutos_trabajados');
            $festivo = (int) round((float) $sesiones->sum('festivo_minutos'));
            $sabado = (int) round((float) $sesiones->sum('sabado_minutos'));
            $ordinarios = max(0, $trabajados - $festivo - $sabado);

            $jornada = $sesiones->first(fn ($s) => $s->jornadaLaboral)?->jornadaLaboral ?? $jornadaActiva;
            $esperadosDiarios = $jornada?->horas_semanales
                ? (int) round(((float) $jornada->horas_semanales / 5) * 60)
                : 480;

            $esFestivo = Carbon::parse($fecha)->isSunday() || $festivo > 0;
            $presupuesto = $esFestivo ? ($festivo + $sabado) : max(0, $ordinarios - $esperadosDiarios);

            foreach ($aprobadas as $h) {
                $pedidos = (int) round((float) $h->horas * 60);
                $rec = min($pedidos, $presupuesto);
                $reconocidos[$h->id] = $rec;
                $presupuesto -= $rec;
            }
        }

        return $reconocidos;
    }

    public function getByUuid(string $uuid): HoraExtra
    {
        return HoraExtra::with(self::WITH)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    /**
     * Crea un registro de horas extras para cada usuario en $data['users'].
     * Retorna la colección de registros creados.
     */
    public function store(array $data): Collection
    {
        return DB::transaction(function () use ($data) {
            $registros = collect();
            $inicio = Carbon::parse($data['fecha'].' '.$data['hora_inicio']);
            $fin = Carbon::parse($data['fecha'].' '.$data['hora_fin']);
            if ($fin->lessThanOrEqualTo($inicio)) {
                $fin->addDay();
            }
            $horas = round($inicio->diffInMinutes($fin) / 60, 2);

            if ($horas < 0.5 || $horas > 24) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'hora_fin' => 'El rango debe representar entre 0.5 y 24 horas.',
                ]);
            }

            $kiosko = !empty($data['kiosko_device_id'])
                ? KioskoDevice::select('id', 'sede_id')->find($data['kiosko_device_id'])
                : null;
            $solicitadoPor = Auth::id();

            foreach ($data['users'] as $userId) {
                $empleado = User::select('id', 'sede_id')->find($userId);
                $sedeId = $data['sede_id'] ?? $kiosko?->sede_id ?? $empleado?->sede_id;

                $horaExtra = HoraExtra::create([
                    'user_id'          => $userId,
                    'sede_id'          => $sedeId,
                    'kiosko_device_id' => $kiosko?->id,
                    'solicitado_por'   => $solicitadoPor,
                    'origen'           => $data['origen'] ?? 'admin',
                    'fecha'            => $data['fecha'],
                    'hora_inicio'      => $data['hora_inicio'],
                    'hora_fin'         => $data['hora_fin'],
                    'horas'            => $horas,
                    'tipo'             => $data['tipo'],
                    'motivo'           => $data['motivo'] ?? null,
                    'status'           => 'pendiente',
                ]);

                $registros->push($horaExtra->load(self::WITH));
            }

            Log::info('Horas extras registradas', [
                'users'  => $data['users'],
                'fecha'  => $data['fecha'],
                'horas'  => $horas,
                'total'  => $registros->count(),
            ]);

            return $registros;
        });
    }

    /**
     * Edita una hora extra propia mientras siga en estado pendiente
     * (incluye las que fueron desaprobadas y volvieron a pendiente).
     */
    public function actualizar(string $uuid, array $data): HoraExtra
    {
        return DB::transaction(function () use ($uuid, $data) {
            $horaExtra = $this->getByUuid($uuid);

            if ($horaExtra->status !== 'pendiente') {
                throw new \LogicException('Solo se pueden editar horas extras en estado pendiente.');
            }

            $inicio = Carbon::parse($data['fecha'].' '.$data['hora_inicio']);
            $fin = Carbon::parse($data['fecha'].' '.$data['hora_fin']);
            if ($fin->lessThanOrEqualTo($inicio)) {
                $fin->addDay();
            }
            $horas = round($inicio->diffInMinutes($fin) / 60, 2);

            if ($horas < 0.5 || $horas > 24) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'hora_fin' => 'El rango debe representar entre 0.5 y 24 horas.',
                ]);
            }

            $kiosko = !empty($data['kiosko_device_id'])
                ? KioskoDevice::select('id', 'sede_id')->find($data['kiosko_device_id'])
                : null;

            $horaExtra->update([
                'sede_id'          => $data['sede_id'] ?? $kiosko?->sede_id ?? $horaExtra->sede_id,
                'kiosko_device_id' => $kiosko?->id,
                'fecha'            => $data['fecha'],
                'hora_inicio'      => $data['hora_inicio'],
                'hora_fin'         => $data['hora_fin'],
                'horas'            => $horas,
                'tipo'             => $data['tipo'],
                'motivo'           => $data['motivo'] ?? null,
            ]);

            Log::info('Hora extra editada', [
                'uuid'    => $horaExtra->uuid,
                'user_id' => $horaExtra->user_id,
            ]);

            return $horaExtra->fresh(self::WITH);
        });
    }

    public function aprobar(string $uuid, ?string $observacion = null): HoraExtra
    {
        return DB::transaction(function () use ($uuid, $observacion) {
            $horaExtra = $this->getByUuid($uuid);

            if ($horaExtra->status !== 'pendiente') {
                throw new \LogicException("La hora extra ya fue {$horaExtra->status}.");
            }

            $horaExtra->update([
                'status'              => 'aprobada',
                'autorizado_por'      => Auth::id(),
                'fecha_gestion'       => now(),
                'observacion_gestion' => $observacion,
            ]);

            Log::info('Hora extra aprobada', [
                'uuid'          => $horaExtra->uuid,
                'user_id'       => $horaExtra->user_id,
                'autorizado_por'=> Auth::id(),
            ]);

            return $horaExtra->fresh(self::WITH);
        });
    }

    public function rechazar(string $uuid, ?string $observacion = null): HoraExtra
    {
        return DB::transaction(function () use ($uuid, $observacion) {
            $horaExtra = $this->getByUuid($uuid);

            if ($horaExtra->status !== 'pendiente') {
                throw new \LogicException("La hora extra ya fue {$horaExtra->status}.");
            }

            $horaExtra->update([
                'status'              => 'rechazada',
                'autorizado_por'      => Auth::id(),
                'fecha_gestion'       => now(),
                'observacion_gestion' => $observacion,
            ]);

            Log::info('Hora extra rechazada', [
                'uuid'    => $horaExtra->uuid,
                'user_id' => $horaExtra->user_id,
            ]);

            return $horaExtra->fresh(self::WITH);
        });
    }

    public function desaprobar(string $uuid, ?string $observacion = null): HoraExtra
    {
        return DB::transaction(function () use ($uuid, $observacion) {
            $horaExtra = $this->getByUuid($uuid);

            if ($horaExtra->status !== 'aprobada') {
                throw new \LogicException('Solo se pueden desaprobar horas extras que estén aprobadas.');
            }

            $horaExtra->update([
                'status'              => 'pendiente',
                'autorizado_por'      => Auth::id(),
                'fecha_gestion'       => now(),
                'observacion_gestion' => $observacion,
            ]);

            Log::info('Hora extra desaprobada', [
                'uuid'          => $horaExtra->uuid,
                'user_id'       => $horaExtra->user_id,
                'desaprobado_por' => Auth::id(),
            ]);

            return $horaExtra->fresh(self::WITH);
        });
    }

    public function destroy(string $uuid): void
    {
        DB::transaction(function () use ($uuid) {
            $horaExtra = $this->getByUuid($uuid);

            if ($horaExtra->status === 'aprobada') {
                throw new \LogicException('No se puede eliminar una hora extra ya aprobada.');
            }

            $horaExtra->delete();

            Log::info('Hora extra eliminada', ['uuid' => $horaExtra->uuid]);
        });
    }

    /**
     * IDs de empleados con más horas extra registradas (cualquier estado),
     * para priorizarlos en el selector del formulario de creación en vez de
     * dejar solo el orden alfabético.
     */
    public function empleadosFrecuentes(array $filters = [], int $limit = 10): array
    {
        return HoraExtra::query()
            ->select('user_id')
            ->selectRaw('COUNT(*) as total')
            ->when(!empty($filters['sede_id']), fn ($q) => $q->where('sede_id', $filters['sede_id']))
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
