<?php

namespace App\Services\Crm;

use App\Models\Crm\GestionCartera;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GestionCarteraService
{
    //CreaR gESTION DE CARTERA


public function crearGestionCartera($data)
{



DB::transaction(function() use ($data) {
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

public function listarGestionCartera(array $filtros)
{
    $query = GestionCartera::query()->with('cliente', 'comercial', 'pagos', 'empresa');

    $user = auth()->user();

    // Solo roles 7 y 9 ven únicamente pendientes
    if (in_array($user->role_id, [7, 9])) {
        $query->where('estado', 'pendiente');
    }

    // Si además el rol 9 solo debe ver sus propios registros
    if ($user->role_id == 9) {
        $query->where('user_comercial_id', $user->id);
    }

 

    if (!empty($filtros['buscar'])) {
        $buscar = $filtros['buscar'];

        $query->where(function ($q) use ($buscar) {
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

        });
    }

    // Solo aplicar filtro estado si el usuario NO es 7 ni 9
    if (!empty($filtros['estado']) && !in_array($user->role_id, [7,9])) {
        $query->where('estado', $filtros['estado']);
    }

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

    $data = $query->paginate($filtros['per_page'] ?? 20);

    return [
        'paginator' => $data,
        'total_cartera' => $totalCartera,
        'total_vencido' => $totalVencido
    ];
}

public function update($id, array $data)
{
    $cartera = GestionCartera::findOrFail($id);

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
    $cartera->estado = $nuevoSaldo <= 0 ? 'completado' : 'pendiente';

    $cartera->save();
}

public function find($id)
{
    return GestionCartera::with('cliente', 'comercial', 'pagos')->findOrFail($id);  

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
}