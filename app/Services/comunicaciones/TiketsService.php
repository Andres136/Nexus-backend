<?php

namespace App\Services\comunicaciones;

use App\Models\comunicaciones\HistorialTickect;
use App\Models\comunicaciones\Ticket;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TiketsService
{
    private const RELACIONES = [
        'solicitante:id,name,email',
        'asignado:id,name,email',
        'producto:id,name,code',
        'departamento:id,nombre',
        'historial.usuario:id,name,email',
    ];

    public function listTickets(array $filters = []): LengthAwarePaginator
    {
        $userId = $this->authenticatedUserId();
        $perPage = min(max((int) ($filters['per_page'] ?? 10), 1), 100);

        return Ticket::with(self::RELACIONES)
            ->where(function ($query) use ($userId) {
                $query->where('user_solicitante_id', $userId)
                    ->orWhere('user_asignado_id', $userId);
            })
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);

                $query->where(function ($q) use ($search) {
                    $q->where('descripcion', 'like', "%{$search}%")
                        ->orWhereHas('solicitante', fn ($user) => $user
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('asignado', fn ($user) => $user
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('producto', fn ($producto) => $producto
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%"))
                        ->orWhereHas('departamento', fn ($departamento) => $departamento
                            ->where('nombre', 'like', "%{$search}%"));
                });
            })
            ->when(!empty($filters['estado']), fn ($query) => $query->where('estado', $filters['estado']))
            ->when(!empty($filters['prioridad']), fn ($query) => $query->where('prioridad', $filters['prioridad']))
            ->when(!empty($filters['producto_id']), fn ($query) => $query->where('producto_id', $filters['producto_id']))
            ->when(!empty($filters['departamento_id']), fn ($query) => $query->where('departamento_id', $filters['departamento_id']))
            ->when(!empty($filters['fecha_desde']), fn ($query) => $query->whereDate('created_at', '>=', $filters['fecha_desde']))
            ->when(!empty($filters['fecha_hasta']), fn ($query) => $query->whereDate('created_at', '<=', $filters['fecha_hasta']))
            ->orderByRaw("FIELD(prioridad, 'alta', 'media', 'baja')")
            ->latest()
            ->paginate($perPage);
    }

    public function getTicket(int $ticketId): Ticket
    {
        $ticket = Ticket::with(self::RELACIONES)->findOrFail($ticketId);

        $this->ensureCanView($ticket);

        return $ticket;
    }

    public function getAssignedOpenSummary(): array
    {
        $userId = $this->authenticatedUserId();
        $openStates = ['pendiente', 'en_proceso'];

        $query = Ticket::with(self::RELACIONES)
            ->where('user_asignado_id', $userId)
            ->whereIn('estado', $openStates);

        return [
            'total' => (clone $query)->count(),
            'tickets' => $query
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }

    public function getEquipmentDowntimeStats(array $filters = []): array
    {
        $now = now();
        $fechaDesde = !empty($filters['fecha_desde'])
            ? Carbon::parse($filters['fecha_desde'])->startOfDay()
            : $now->copy()->startOfMonth();
        $fechaHasta = !empty($filters['fecha_hasta'])
            ? Carbon::parse($filters['fecha_hasta'])->endOfDay()
            : $now->copy();

        if ($fechaHasta->lessThan($fechaDesde)) {
            [$fechaDesde, $fechaHasta] = [$fechaHasta->copy()->startOfDay(), $fechaDesde->copy()->endOfDay()];
        }

        $horasPeriodo = max($fechaDesde->floatDiffInHours($fechaHasta), 0.01);

        $tickets = Ticket::with([
                'producto:id,name,code',
                'solicitante:id,name,email',
                'asignado:id,name,email',
            ])
            ->whereNotNull('producto_id')
            ->when(!empty($filters['producto_id']), fn ($query) => $query->where('producto_id', $filters['producto_id']))
            ->where('created_at', '<=', $fechaHasta)
            ->where(function ($query) use ($fechaDesde) {
                $query->whereNull('fecha_solucion')
                    ->orWhere('fecha_solucion', '>=', $fechaDesde);
            })
            ->get();

        $productos = [];
        $horasParadoTotal = 0.0;

        foreach ($tickets as $ticket) {
            $inicioParada = Carbon::parse($ticket->created_at)->max($fechaDesde);
            $finParada = Carbon::parse($ticket->fecha_solucion ?? now())->min($fechaHasta);

            if ($finParada->lessThanOrEqualTo($inicioParada)) {
                continue;
            }

            $horasParado = round($inicioParada->floatDiffInHours($finParada), 2);
            $horasParadoTotal += $horasParado;

            $productoId = (int) $ticket->producto_id;
            if (!isset($productos[$productoId])) {
                $productos[$productoId] = [
                    'producto' => [
                        'id' => $productoId,
                        'name' => $ticket->producto?->name,
                        'code' => $ticket->producto?->code,
                    ],
                    'tickets_total' => 0,
                    'tickets_abiertos' => 0,
                    'tickets_cerrados' => 0,
                    'horas_periodo' => round($horasPeriodo, 2),
                    'horas_parado' => 0.0,
                    'porcentaje_parada' => 0.0,
                    'porcentaje_disponibilidad' => 100.0,
                    'tickets' => [],
                ];
            }

            $productos[$productoId]['tickets_total']++;
            $productos[$productoId][$ticket->estado === 'cerrado' ? 'tickets_cerrados' : 'tickets_abiertos']++;
            $productos[$productoId]['horas_parado'] = round($productos[$productoId]['horas_parado'] + $horasParado, 2);
            $productos[$productoId]['tickets'][] = [
                'id' => $ticket->id,
                'estado' => $ticket->estado,
                'prioridad' => $ticket->prioridad,
                'descripcion' => $ticket->descripcion,
                'inicio_parada' => $inicioParada->toDateTimeString(),
                'fin_parada' => $finParada->toDateTimeString(),
                'horas_parado' => $horasParado,
                'solicitante' => $ticket->solicitante,
                'asignado' => $ticket->asignado,
            ];
        }

        foreach ($productos as &$producto) {
            $porcentajeParada = min(($producto['horas_parado'] / $horasPeriodo) * 100, 100);
            $producto['porcentaje_parada'] = round($porcentajeParada, 2);
            $producto['porcentaje_disponibilidad'] = round(max(100 - $porcentajeParada, 0), 2);
        }
        unset($producto);

        $porcentajeParadaTotal = min(($horasParadoTotal / $horasPeriodo) * 100, 100);

        return [
            'periodo' => [
                'fecha_desde' => $fechaDesde->toDateTimeString(),
                'fecha_hasta' => $fechaHasta->toDateTimeString(),
                'horas_periodo' => round($horasPeriodo, 2),
            ],
            'resumen' => [
                'productos_con_tickets' => count($productos),
                'tickets_total' => $tickets->count(),
                'horas_parado_total' => round($horasParadoTotal, 2),
                'porcentaje_parada_total' => round($porcentajeParadaTotal, 2),
                'porcentaje_disponibilidad_total' => round(max(100 - $porcentajeParadaTotal, 0), 2),
            ],
            'productos' => array_values($productos),
        ];
    }

    public function createTicket(array $data): Ticket
    {
        return DB::transaction(function () use ($data) {
            $data['user_solicitante_id'] = $this->authenticatedUserId();
            $data['estado'] = $data['estado'] ?? 'pendiente';
            $data['prioridad'] = $data['prioridad'] ?? 'media';

            if ($data['estado'] === 'cerrado') {
                $data['fecha_solucion'] = $data['fecha_solucion'] ?? now();
            }

            if (($data['archivo'] ?? null) instanceof UploadedFile) {
                $data['archivo'] = $this->storeFile($data['archivo'], 'tickets');
            }

            if (!empty($data['archivos'])) {
                $data['archivos'] = $this->storeFiles($data['archivos'], 'tickets');
            }

            $ticket = Ticket::create($data);

            $this->createHistory($ticket, [
                'comentario' => 'Ticket creado.',
            ]);

            return $ticket->load(self::RELACIONES);
        });
    }

    public function updateTicket(int $ticketId, array $data): Ticket
    {
        return DB::transaction(function () use ($ticketId, $data) {
            $ticket = Ticket::findOrFail($ticketId);

            $comentario = $data['comentario'] ?? null;
            unset($data['comentario']);

            if ($comentario) {
                $this->ensureCanView($ticket);
            }

            if (array_key_exists('estado', $data)) {
                $this->ensureCanChangeStatus($ticket);
            }

            if ($this->hasEditableTicketData($data)) {
                $this->ensureCanModify($ticket);
            }

            if (($data['archivo'] ?? null) instanceof UploadedFile) {
                $this->deleteFile($ticket->archivo);
                $data['archivo'] = $this->storeFile($data['archivo'], 'tickets');
            }

            if (!empty($data['archivos'])) {
                $this->deleteFiles($ticket->archivos);
                $data['archivos'] = $this->storeFiles($data['archivos'], 'tickets');
            }

            if (array_key_exists('estado', $data)) {
                if ($data['estado'] === 'cerrado') {
                    $data['fecha_solucion'] = $data['fecha_solucion'] ?? now();
                } elseif (! array_key_exists('fecha_solucion', $data)) {
                    $data['fecha_solucion'] = null;
                }
            }

            $estadoAnterior = $ticket->estado;
            $asignadoAnterior = $ticket->user_asignado_id;

            $ticket->update($data);

            if ($comentario) {
                $this->createHistory($ticket, ['comentario' => $comentario]);
            }

            if (array_key_exists('estado', $data) && $estadoAnterior !== $ticket->estado) {
                $this->createHistory($ticket, [
                    'comentario' => "Estado actualizado de {$estadoAnterior} a {$ticket->estado}.",
                ]);
            }

            if (array_key_exists('user_asignado_id', $data) && (int) $asignadoAnterior !== (int) $ticket->user_asignado_id) {
                $this->createHistory($ticket, [
                    'comentario' => $ticket->user_asignado_id
                        ? "Ticket asignado al usuario {$ticket->user_asignado_id}."
                        : 'Ticket quedó sin usuario asignado.',
                ]);
            }

            return $ticket->fresh(self::RELACIONES);
        });
    }

    public function changeStatus(int $ticketId, string $estado, ?string $comentario = null): Ticket
    {
        return DB::transaction(function () use ($ticketId, $estado, $comentario) {
            $ticket = Ticket::findOrFail($ticketId);

            $this->ensureCanChangeStatus($ticket);

            $data = [
                'estado' => $estado,
                'fecha_solucion' => $estado === 'cerrado' ? now() : null,
            ];

            $estadoAnterior = $ticket->estado;

            $ticket->update($data);

            if ($comentario) {
                $this->createHistory($ticket, ['comentario' => $comentario]);
            }

            if ($estadoAnterior !== $ticket->estado) {
                $this->createHistory($ticket, [
                    'comentario' => "Estado actualizado de {$estadoAnterior} a {$ticket->estado}.",
                ]);
            }

            return $ticket->fresh(self::RELACIONES);
        });
    }

    public function addHistory(int $ticketId, array $data): HistorialTickect
    {
        return DB::transaction(function () use ($ticketId, $data) {
            $ticket = Ticket::findOrFail($ticketId);

            $this->ensureCanView($ticket);

            $cerrarTicket = (bool) ($data['cerrar'] ?? false);

            if ($cerrarTicket) {
                $this->ensureCanChangeStatus($ticket);
            }

            if (($data['soporte'] ?? null) instanceof UploadedFile) {
                $data['soporte'] = $this->storeFile($data['soporte'], 'tickets/historial');
            }

            if (!empty($data['soportes'])) {
                $data['soportes'] = $this->storeFiles($data['soportes'], 'tickets/historial');
            }

            $historial = $this->createHistory($ticket, $data);

            if ($cerrarTicket && $ticket->estado !== 'cerrado') {
                $estadoAnterior = $ticket->estado;

                $ticket->update([
                    'estado' => 'cerrado',
                    'fecha_solucion' => now(),
                ]);

                $this->createHistory($ticket, [
                    'comentario' => "Estado actualizado de {$estadoAnterior} a cerrado.",
                ]);
            }

            return $historial;
        });
    }

    public function deleteTicket(int $ticketId): void
    {
        DB::transaction(function () use ($ticketId) {
            $ticket = Ticket::with('historial')->findOrFail($ticketId);

            $this->ensureCanModify($ticket);

            $this->deleteFile($ticket->archivo);
            $this->deleteFiles($ticket->archivos);

            foreach ($ticket->historial as $historial) {
                $this->deleteFile($historial->soporte);
                $this->deleteFiles($historial->soportes);
            }

            $ticket->delete();
        });
    }

    private function createHistory(Ticket $ticket, array $data): HistorialTickect
    {
        return HistorialTickect::create([
            'ticket_id' => $ticket->id,
            'user_id' => $data['user_id'] ?? $this->authenticatedUserId(),
            'comentario' => $data['comentario'],
            'soporte' => $data['soporte'] ?? null,
            'soportes' => $data['soportes'] ?? null,
            'link' => $data['link'] ?? null,
        ])->load('usuario:id,name,email');
    }

    private function ensureCanView(Ticket $ticket): void
    {
        $userId = $this->authenticatedUserId();

        if (
            (int) $ticket->user_solicitante_id !== $userId
            && (int) $ticket->user_asignado_id !== $userId
        ) {
            throw new AuthorizationException('No tienes permiso para ver este ticket.');
        }
    }

    private function ensureCanModify(Ticket $ticket): void
    {
        if ((int) $ticket->user_solicitante_id !== $this->authenticatedUserId()) {
            throw new AuthorizationException('Solo el usuario que creó el ticket puede editarlo o eliminarlo.');
        }
    }

    private function ensureCanChangeStatus(Ticket $ticket): void
    {
        if ((int) $ticket->user_asignado_id !== $this->authenticatedUserId()) {
            throw new AuthorizationException('Solo el usuario asignado al ticket puede cambiar su estado.');
        }
    }

    private function hasEditableTicketData(array $data): bool
    {
        return ! empty(array_intersect(array_keys($data), [
            'user_asignado_id',
            'producto_id',
            'departamento_id',
            'descripcion',
            'archivo',
            'archivos',
            'prioridad',
            'fecha_entrega',
            'hora_entrega',
            'fecha_solucion',
        ]));
    }

    private function authenticatedUserId(): int
    {
        return (int) Auth::id();
    }

    private function storeFile(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, 'public');
    }

    /**
     * @param array<int, UploadedFile> $files
     * @return array<int, string>
     */
    private function storeFiles(array $files, string $directory): array
    {
        return collect($files)
            ->filter(fn ($file) => $file instanceof UploadedFile)
            ->map(fn (UploadedFile $file) => $this->storeFile($file, $directory))
            ->values()
            ->all();
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    private function deleteFiles(?array $paths): void
    {
        foreach ($paths ?? [] as $path) {
            $this->deleteFile($path);
        }
    }
}
