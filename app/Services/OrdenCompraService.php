<?php
namespace App\Services;

use App\Models\Crm\Orden_Compra;
use App\Models\Departamentos;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OrdenCompraNotificacion;
use App\Notifications\OrdenesPorVencerNotificacion;
use Illuminate\Support\Facades\Cache;

class OrdenCompraService
{
    /**
     * Notifica a los usuarios sobre las órdenes de compra pendientes.
     *
     * @return void
     */

public function notificarOrdenesPorVencer()
{
    $hoy = Carbon::now()->toDateString();

    if (Cache::has('notificacion_ordenes_' . $hoy)) {
        return;
    }

    $rolesPermitidos= [4,6]; // Agrega aquí los role_id permitidos
    $operacionesId = Departamentos::where('nombre', 'Operaciones')->value('id');

    $ordenes = Orden_Compra::with(['user', 'cliente', 'sede'])
        ->where('estado_id', 1)
        ->whereNotNull('sede_id')
        ->get();

foreach ($ordenes as $orden) {
    $fechaEntrega = Carbon::parse($orden->fecha_entrega);
    $dosDiasAntes = $fechaEntrega->copy()->subDays(2);

    if (Carbon::now()->greaterThanOrEqualTo($dosDiasAntes)) {
            // Notificar a usuarios del departamento de Operaciones en la misma sede
        if ($orden->departamento_id == $operacionesId) {
            $usuariosSede = User::where('sede_id', $orden->sede_id)
                ->where('departamento_id', $operacionesId)
                ->whereIn('role_id', $rolesPermitidos) // Filtrar por roles permitidos
                ->whereNotNull('email')
                ->get();

            if ($usuariosSede->count() > 0) {
                Notification::send($usuariosSede, new OrdenesPorVencerNotificacion($orden));
            }
        }

        // Siempre notifica al creador de la orden si existe
        if ($orden->user) {
            $orden->user->notify(new OrdenesPorVencerNotificacion($orden));
        }
    }
}
Cache::put('notificacion_ordenes_' . $hoy, true, now()->addDay());
  

}
// ===============================
//  SERVICE: OrdenCompraService
// ===============================

public function misOrdenesSearch($request)
{
    $userId = auth()->id();

    $query = Orden_Compra::with([
            'cliente',
            'estado',
            'empresa'
        ])
        ->where('user_id', $userId)
        ->select([
            'id',
            'cliente_id',
            'estado_id',
            'empresa_id',
            'fecha_entrega',
            'valor_total',
            'created_at',
            'observaciones'
        ]);

    // ✅ Si no hay búsqueda, limitar últimos 5 meses
    if (!$request->filled('search')) {
        $query->whereDate(
            'created_at',
            '>=',
            now()->subMonths(5)
        );
    }

    // 🔍 Filtro por estado
    if ($request->filled('estado_id')) {
        $query->where('estado_id', $request->estado_id);
    }

    // 🔍 Filtro fechas
    if (
        $request->filled('fecha_inicio') &&
        $request->filled('fecha_fin')
    ) {
        $query->whereBetween('created_at', [
            $request->fecha_inicio,
            $request->fecha_fin
        ]);
    }

    // 🔍 Buscador
    if ($request->filled('search')) {
        $search = $request->search;

        $query->where(function ($q) use ($search) {
            $q->where('id', 'like', "%{$search}%")
              ->orWhere('observaciones', 'like', "%{$search}%")
              ->orWhereHas('cliente', function ($cliente) use ($search) {
                  $cliente->where('nombre', 'like', "%{$search}%");
              });
        });
    }

    return $query
        ->orderByDesc('created_at')
        ->paginate(
            $request->filled('search') ? 50 : ($request->per_page ?? 15)
        );
}

}