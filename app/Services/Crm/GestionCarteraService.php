<?php

namespace App\Services\Crm;

use App\EstadoEnum;
use App\Mail\OrdenCompraCarteraClienteMail;
use App\Models\Crm\GestionCartera;
use App\Models\Crm\Orden_Compra;
use App\Models\User;
use App\Notifications\Crm\CarteraClienteAlCrearOcNotification;
use App\Notifications\Crm\FacturaCarteraNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class GestionCarteraService
{
    /**
     * Verifica si el cliente de la Orden de Compra tiene facturas vencidas o
     * próximas a vencer (mismo umbral de 8 días que VerificarFacturasCartera).
     * Si es así, notifica al usuario que creó la orden Y envía un correo al
     * cliente (mismo patrón ya usado al generar OT y al iniciar alistamiento).
     * Devuelve un resumen para el frontend, o null si no hay nada.
     */
    public function verificarYNotificarCarteraCliente(Orden_Compra $ordenCompra): ?array
    {
        ['vencidas' => $vencidas, 'proximas' => $proximas] = $this->obtenerCarteraRelevante($ordenCompra->cliente_id);

        $resumen = $this->resumirCartera($vencidas, $proximas);
        if (!$resumen) {
            return null;
        }

        $ordenCompra->user?->notify(new CarteraClienteAlCrearOcNotification($ordenCompra, $resumen));

        $cliente = $ordenCompra->cliente;
        if ($cliente && $cliente->email) {
            Mail::to($cliente->email)->send(
                new OrdenCompraCarteraClienteMail($ordenCompra, $cliente, $resumen)
            );
        }

        return $resumen;
    }

    /**
     * Igual que verificarYNotificarCarteraCliente() pero de solo lectura: no
     * notifica al comercial (eso ya se hace al crear la OC). Se usa para
     * incluir el estado de cartera del cliente en otros avisos, como el de
     * generación de Orden de Trabajo.
     */
    public function resumenCarteraCliente(int $clienteId): ?array
    {
        ['vencidas' => $vencidas, 'proximas' => $proximas] = $this->obtenerCarteraRelevante($clienteId);

        return $this->resumirCartera($vencidas, $proximas);
    }

    private function obtenerCarteraRelevante(int $clienteId): array
    {
        $hoy = now();

        $vencidas = GestionCartera::where('cliente_id', $clienteId)
            ->where('estado', 'pendiente')
            ->whereDate('fecha_vencimiento', '<', $hoy)
            ->get();

        $proximas = GestionCartera::where('cliente_id', $clienteId)
            ->where('estado', 'pendiente')
            ->whereBetween('fecha_vencimiento', [$hoy, $hoy->copy()->addDays(8)])
            ->get();

        return ['vencidas' => $vencidas, 'proximas' => $proximas];
    }

    /**
     * IDs de clientes con cartera vencida o próxima a vencer (mismo umbral de
     * 8 días que el resto del módulo). Se usa para el recordatorio periódico
     * al cliente, que corre para todos los clientes de una sola vez.
     */
    public function clientesConCarteraPendiente()
    {
        $hoy = now();

        return GestionCartera::where('estado', 'pendiente')
            ->where(function ($q) use ($hoy) {
                $q->whereDate('fecha_vencimiento', '<', $hoy)
                  ->orWhereBetween('fecha_vencimiento', [$hoy, $hoy->copy()->addDays(8)]);
            })
            ->pluck('cliente_id')
            ->unique();
    }

    private function resumirCartera($vencidas, $proximas): ?array
    {
        if ($vencidas->isEmpty() && $proximas->isEmpty()) {
            return null;
        }

        return [
            'tiene_vencida'     => $vencidas->isNotEmpty(),
            'tiene_proxima'     => $proximas->isNotEmpty(),
            'total_vencido'     => (float) $vencidas->sum('saldo_pendiente'),
            'total_proximo'     => (float) $proximas->sum('saldo_pendiente'),
            'facturas_vencidas' => $vencidas->pluck('numero_factura')->values(),
            'facturas_proximas' => $proximas->pluck('numero_factura')->values(),
        ];
    }

    //CreaR gESTION DE CARTERA


public function crearGestionCartera($data)
{

return DB::transaction(function() use ($data) {
 $resultados = [];

    foreach ($data['registros'] as $registro) {

        $fechaFactura = Carbon::parse($registro['fecha_factura']);
        $diasCredito = (int) $registro['dias_credito'];

        $fechaVencimiento = $fechaFactura->addDays($diasCredito);
$base = (float) ($registro['base'] ?? 0);
$iva = (float) ($registro['iva'] ?? 0);
$reteRenta = (float) ($registro['rete_renta'] ?? 0);
$reteIca = (float) ($registro['rete_ica'] ?? 0);

$valorTotal = $base + $iva - $reteRenta - $reteIca;
        $gestionCartera = GestionCartera::create([
            'user_id' => auth()->id(),
            'empresa_id' => $registro['empresa_id'],
            'numero_factura' => $registro['numero_factura'],
            'user_comercial_id' => $registro['user_comercial_id'],
            'cliente_id' => $registro['cliente_id'],
            'valor_total' => $valorTotal,
            'saldo_pendiente' => $valorTotal,
            'fecha_vencimiento' => $fechaVencimiento,
            'observaciones' => $registro['observaciones'] ?? null,
            'fecha_factura' => $registro['fecha_factura'],
            'estado' => $registro['estado'] ?? 'pendiente',
            'dias_credito' => $diasCredito,
            'base' => $registro['base'] ?? null,
            'iva' => $registro['iva'] ?? null,
            'rete_renta' => $registro['rete_renta'] ?? null,
            'rete_ica' => $registro['rete_ica'] ?? null
        ]);

        $resultados[] = $gestionCartera;
    }

    return $resultados;
});
   
}

    //CREAR UN ABONO A LA GESTION DE CARTERA
// CREAR UN ABONO A LA GESTION DE CARTERA
public function crearAbono($gestionCarteraId, $data)
{
    $gestionCartera = GestionCartera::findOrFail($gestionCarteraId);

    // Crear el pago
    $abono = $gestionCartera->pagos()->create([
        'valor_pago' => $data['valor_pago'],
        'fecha_pago' => $data['fecha_pago']
    ]);

    // Calcular nuevo saldo
    $nuevoSaldo = $gestionCartera->saldo_pendiente - $data['valor_pago'];

    if ($nuevoSaldo <= 0) {
        $gestionCartera->saldo_pendiente = 0;
        $gestionCartera->estado = 'completado';
    } else {
        $gestionCartera->saldo_pendiente = $nuevoSaldo;
    }

    $gestionCartera->save();

    return $abono;
}

/**
 * Restringe la query al alcance por rol: los roles 7 y 9 solo ven
 * pendientes, y el rol 9 además solo ve sus propios registros. Se usa
 * tanto en el listado como en find()/update() para que un comercial no
 * pueda ver ni editar (por id directo) facturas fuera de su alcance.
 */
private function aplicarScopePorRol($query)
{
    $user = auth()->user();

    if (in_array($user->role_id, [7, 9])) {
        $query->where('estado', 'pendiente');
    }

    if ($user->role_id == 9) {
        $query->where('user_comercial_id', $user->id);
    }

    return $query;
}

private function aplicarFiltros($query, array $filtros)
{
    $user = auth()->user();

    $this->aplicarScopePorRol($query);

    if (!empty($filtros['buscar'])) {
        $buscar = $filtros['buscar'];
        $buscarPor = $filtros['buscar_por'] ?? null;

        $query->where(function ($q) use ($buscar, $buscarPor) {
            if ($buscarPor === 'factura') {
                $q->where('numero_factura', 'like', "%{$buscar}%");
            } elseif ($buscarPor === 'comercial') {
                $q->whereHas('comercial', function ($q3) use ($buscar) {
                    $q3->where('name', 'like', "%{$buscar}%");
                });
            } elseif ($buscarPor === 'empresa') {
                $q->whereHas('empresa', function ($q4) use ($buscar) {
                    $q4->where('nombre', 'like', "%{$buscar}%");
                });
            } elseif ($buscarPor === 'cliente') {
                $q->whereHas('cliente', function ($q2) use ($buscar) {
                    $q2->where('nombre', 'like', "%{$buscar}%");
                });
            } else {
                // Sin selector (compatibilidad): busca en todos los campos a la vez
                $q->where('numero_factura', 'like', "%{$buscar}%")
                  ->orWhereHas('cliente', function ($q2) use ($buscar) {
                      $q2->where('nombre', 'like', "%{$buscar}%");
                  })
                  ->orWhereHas('comercial', function ($q3) use ($buscar) {
                      $q3->where('name', 'like', "%{$buscar}%");
                  })
                  ->orWhereHas('empresa', function ($q4) use ($buscar) {
                      $q4->where('nombre', 'like', "%{$buscar}%");
                  });
            }
        });
    }

    // Solo aplicar filtro estado si el usuario NO es 7 ni 9
    if (!empty($filtros['estado']) && !in_array($user->role_id, [7,9])) {
        $query->where('estado', $filtros['estado']);
    }

    if (!empty($filtros['cliente_id'])) {
        $query->where('cliente_id', $filtros['cliente_id']);
    }

    if (!empty($filtros['user_comercial_id'])) {
        $query->where('user_comercial_id', $filtros['user_comercial_id']);
    }

    if (!empty($filtros['fecha_inicio'])) {
        $query->whereDate('fecha_factura', '>=', $filtros['fecha_inicio']);
    }

    if (!empty($filtros['fecha_fin'])) {
        $query->whereDate('fecha_factura', '<=', $filtros['fecha_fin']);
    }

    return $query;
}

public function listarGestionCartera(array $filtros)
{
    $query = $this->aplicarFiltros(
        GestionCartera::query()->with('cliente', 'comercial', 'pagos', 'empresa'),
        $filtros
    );

    // Calcular total SIN alterar la query principal
    $totalCartera = (clone $query)->sum('saldo_pendiente');
    $totalVencido = (clone $query)
    ->where('estado', 'pendiente')
    ->whereDate('fecha_vencimiento', '<', now())
    ->sum('saldo_pendiente');

    $query->orderByRaw("
        CASE
            WHEN estado = 'pendiente' AND fecha_vencimiento < CURDATE() THEN 0
            WHEN estado = 'pendiente' THEN 1
            ELSE 2
        END
    ");

    $query->orderBy('fecha_vencimiento', 'asc');
$query->orderByRaw("CAST(SUBSTRING(numero_factura, 4) AS UNSIGNED) ASC");

    $data = $query->paginate($filtros['per_page'] ?? 20);

    return [
        'paginator' => $data,
        'total_cartera' => $totalCartera,
        'total_vencido' => $totalVencido
    ];
}

/**
 * Registros de cartera para exportar a Excel, respetando los mismos
 * filtros y el mismo alcance por rol que listarGestionCartera().
 */
public function exportarGestionCartera(array $filtros)
{
    return $this->aplicarFiltros(
        GestionCartera::query()->with('cliente', 'comercial', 'empresa'),
        $filtros
    )
        ->orderBy('fecha_vencimiento', 'asc')
        ->get();
}

public function update($id, array $data)
{
    $cartera = $this->aplicarScopePorRol(GestionCartera::query())->findOrFail($id);

    //  recalcular fechas
    $fechaFactura = isset($data['fecha_factura'])
        ? Carbon::parse($data['fecha_factura'])
        : Carbon::parse($cartera->fecha_factura);

    $diasCredito = isset($data['dias_credito'])
        ? (int) $data['dias_credito']
        : (int) $cartera->dias_credito;

    if (isset($data['fecha_factura']) || isset($data['dias_credito'])) {
        $data['fecha_vencimiento'] = $fechaFactura->copy()->addDays($diasCredito);
    }

    //  recalcular total
    $base = (float) ($data['base'] ?? $cartera->base);
    $iva = (float) ($data['iva'] ?? $cartera->iva);
    $reteRenta = (float) ($data['rete_renta'] ?? $cartera->rete_renta);
    $reteIca = (float) ($data['rete_ica'] ?? $cartera->rete_ica);

    $data['valor_total'] = $base + $iva - $reteRenta - $reteIca;

    //  actualizar
    $cartera->update(array_filter($data, fn($v) => $v !== null && $v !== ''));

    //  recalcular saldo (IMPORTANTE)
    $this->recalcularSaldo($cartera);

    return $cartera->fresh(['cliente', 'comercial', 'pagos']);
}
private function recalcularSaldo($cartera)
{
    $totalPagado = $cartera->pagos()->sum('valor_pago');

    $nuevoSaldo = $cartera->valor_total - $totalPagado;

    $cartera->saldo_pendiente = max(0, $nuevoSaldo);

    // No pisar una deuda cancelada/condonada: solo alternar entre
    // pendiente/completado si el estado actual no es 'cancelado'.
    if ($cartera->estado !== 'cancelado') {
        $cartera->estado = $nuevoSaldo <= 0 ? 'completado' : 'pendiente';
    }

    $cartera->save();
}

public function find($id)
{
    return $this->aplicarScopePorRol(GestionCartera::query())
        ->with('cliente', 'comercial', 'pagos')
        ->findOrFail($id);
}

public function cancelarDeuda($id)
{
    $cartera = GestionCartera::findOrFail($id);

    DB::transaction(function () use ($cartera) {

        // 🔥 guardar el valor REAL de la deuda antes de modificar
        $valorDeuda = $cartera->saldo_pendiente;

        // actualizar deuda
        $cartera->update([
            'saldo_pendiente' => 0,
            'estado' => 'cancelado'
        ]);

        // registrar el pago con el valor correcto
        $cartera->pagos()->create([
            'valor_pago' => $valorDeuda,
            'fecha_pago' => now(),
            'observacion' => 'Deuda cancelada manualmente'
        ]);
    });

    return $cartera;
}


public function lineaTiempoAnual($year = null)
{
    $year = $year ?? now()->year;

    $vencidoMensual = GestionCartera::select(
        DB::raw('MONTH(fecha_vencimiento) as mes'),
        DB::raw('SUM(saldo_pendiente) as total')
    )
        ->whereYear('fecha_vencimiento', $year)
        ->where('saldo_pendiente', '>', 0)
        ->whereDate('fecha_vencimiento', '<', now())
        ->groupBy(DB::raw('MONTH(fecha_vencimiento)'))
        ->get();

    $meses = collect(range(1,12))->map(function ($mes) use ($vencidoMensual) {

        $registro = $vencidoMensual->firstWhere('mes', $mes);

        return [
            'mes' => $mes,
            'total' => $registro ? (float)$registro->total : 0
        ];
    });

    $totalVencido = $meses->sum('total');

    $totalCartera = GestionCartera::where('saldo_pendiente','>',0)->sum('saldo_pendiente');

    $porcentaje = $totalCartera > 0
        ? round(($totalVencido / $totalCartera) * 100, 2)
        : 0;

    return [
        'year' => $year,
        'timeline' => $meses,
        'total_vencido' => $totalVencido,
        'total_cartera' => $totalCartera,
        'porcentaje_vencido' => $porcentaje
    ];
}

//Reacaudo Semanal
public function recaudoSemanal($year = null)
{
    $year = $year ?? now()->year;

    $recaudoSemanal = DB::table('gestion_cartera_pivote')
        ->select(
            DB::raw('WEEK(fecha_pago, 1) as semana'),
            DB::raw('SUM(valor_pago) as total')
        )
        ->whereYear('fecha_pago', $year)
        ->groupBy(DB::raw('WEEK(fecha_pago, 1)'))
        ->orderBy('semana')
        ->get();

    $semanas = collect(range(1, 52))->map(function ($semana) use ($recaudoSemanal) {

        $registro = $recaudoSemanal->firstWhere('semana', $semana);

        return [
            'semana' => $semana,
            'es_actual' => $semana === now()->weekOfYear,
            'total' => $registro ? (float)$registro->total : 0
        ];
    });

    $totalRecaudo = $semanas->sum('total');

    return [
        'year' => $year,
        'semana_actual' => now()->weekOfYear,
        'timeline' => $semanas,
        'total_recaudo' => $totalRecaudo
    ];
}
//ELIMINAR  FACTURA (ANULAR)
public function anularFactura($id)
{
   $cartera = GestionCartera::findOrFail($id);
   // ELIMINAR
   $cartera->delete();
   return $cartera;

}

/**
 * Lista las Órdenes de Compra cuyo cliente tiene cartera vencida, para que
 * el responsable de proceso decida si bloquearlas. Cada orden se devuelve
 * con un atributo `cartera_info` (mismo formato de resumenCarteraCliente()).
 */
public function ordenesCompraConCarteraVencida(array $filtros = [])
{
    $clientesVencidos = GestionCartera::where('estado', 'pendiente')
        ->whereDate('fecha_vencimiento', '<', now())
        ->pluck('cliente_id')
        ->unique();

    $query = Orden_Compra::with(['cliente', 'user', 'estado'])
        ->whereIn('cliente_id', $clientesVencidos)
        // Una orden Completada ya no puede generar OT ni despacharse: no hay nada que
        // activar/desactivar sobre ella, así que no tiene sentido mostrarla aquí.
        ->whereNot('estado_id', EstadoEnum::COMPLETADO->value);

    if (!empty($filtros['buscar'])) {
        $buscar = $filtros['buscar'];
        $query->whereHas('cliente', function ($q) use ($buscar) {
            $q->where('nombre', 'like', "%{$buscar}%");
        });
    }

    // 'activa' = distinta de Inactivo (estado_id 4), 'inactiva' = bloqueada, '' = todas
    if (($filtros['estado'] ?? '') === 'inactiva') {
        $query->where('estado_id', EstadoEnum::INACTIVO->value);
    } elseif (($filtros['estado'] ?? '') === 'activa') {
        $query->whereNot('estado_id', EstadoEnum::INACTIVO->value);
    }

    // Total del valor de las órdenes que cumplen los filtros (no solo la página
    // actual), para que el responsable dimensione el impacto antes de decidir.
    $totalValor = (clone $query)->sum('valor_total');

    $ordenes = $query->orderByDesc('created_at')->paginate($filtros['per_page'] ?? 15);

    $resumenPorCliente = [];
    $ordenes->getCollection()->transform(function ($oc) use (&$resumenPorCliente) {
        if (!array_key_exists($oc->cliente_id, $resumenPorCliente)) {
            $resumenPorCliente[$oc->cliente_id] = $this->resumenCarteraCliente($oc->cliente_id);
        }
        $oc->cartera_info = $resumenPorCliente[$oc->cliente_id];
        return $oc;
    });

    return [
        'paginator' => $ordenes,
        'total_valor' => (float) $totalValor,
    ];
}

/**
 * Ranking de clientes por deuda pendiente y por pago reciente, para el
 * Informe de rendimiento. Es puramente informativo: nunca incluye ni
 * calcula ningún criterio de "candidato a bloqueo" — esa decisión es
 * exclusiva del administrador humano.
 */
public function rankingClientesCartera(int $anio, int $mes, int $topN = 10): array
{
    return [
        'top_deudores' => $this->topClientesPorDeuda($topN),
        'top_pagos_recientes' => $this->topClientesPorPagoReciente($anio, $mes, $topN),
    ];
}

private function topClientesPorDeuda(int $topN): array
{
    return DB::table('gestion_cartera')
        ->join('clientes', 'clientes.id', '=', 'gestion_cartera.cliente_id')
        ->where('gestion_cartera.estado', 'pendiente')
        ->where('gestion_cartera.saldo_pendiente', '>', 0)
        ->groupBy('gestion_cartera.cliente_id', 'clientes.nombre')
        ->selectRaw('gestion_cartera.cliente_id, clientes.nombre as cliente, '
            . 'SUM(gestion_cartera.saldo_pendiente) as total_deuda, '
            . 'MIN(gestion_cartera.fecha_vencimiento) as vencimiento_mas_antiguo, '
            . 'COUNT(*) as facturas_pendientes')
        ->orderByDesc('total_deuda')
        ->limit($topN)
        ->get()
        ->map(function ($r) {
            $vencimiento = Carbon::parse($r->vencimiento_mas_antiguo);
            return [
                'cliente_id' => $r->cliente_id,
                'cliente' => $r->cliente,
                'total_deuda' => (float) $r->total_deuda,
                'dias_vencido_mas_antiguo' => $vencimiento->isPast() ? $vencimiento->diffInDays(now()) : 0,
                'facturas_pendientes' => (int) $r->facturas_pendientes,
            ];
        })->values()->toArray();
}

private function topClientesPorPagoReciente(int $anio, int $mes, int $topN): array
{
    return DB::table('gestion_cartera_pivote')
        ->join('gestion_cartera', 'gestion_cartera.id', '=', 'gestion_cartera_pivote.gestion_cartera_id')
        ->join('clientes', 'clientes.id', '=', 'gestion_cartera.cliente_id')
        ->whereYear('gestion_cartera_pivote.fecha_pago', $anio)
        ->whereMonth('gestion_cartera_pivote.fecha_pago', $mes)
        ->groupBy('gestion_cartera.cliente_id', 'clientes.nombre')
        ->selectRaw('gestion_cartera.cliente_id, clientes.nombre as cliente, '
            . 'SUM(gestion_cartera_pivote.valor_pago) as total_pagado, '
            . 'MAX(gestion_cartera_pivote.fecha_pago) as ultimo_pago')
        ->orderByDesc('total_pagado')
        ->limit($topN)
        ->get()
        ->map(fn ($r) => [
            'cliente_id' => $r->cliente_id,
            'cliente' => $r->cliente,
            'total_pagado_mes' => (float) $r->total_pagado,
            'ultimo_pago' => $r->ultimo_pago,
        ])->values()->toArray();
}
}