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

        $gestionCartera = GestionCartera::create([
            'user_id' => auth()->id(),
            'empresa_id' => $registro['empresa_id'],
            'numero_factura' => $registro['numero_factura'],
            'user_comercial_id' => $registro['user_comercial_id'],
            'cliente_id' => $registro['cliente_id'],
            'valor_total' => $registro['valor_total'],
            'saldo_pendiente' => $registro['saldo_pendiente'] ?? $registro['valor_total'],
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
        'total_cartera' => $totalCartera
    ];
}

public function update($id, array $data)
{
    $cartera = GestionCartera::findOrFail($id);

    $cartera->update([
        'fecha_vencimiento' => $data['fecha_vencimiento'] ?? $cartera->fecha_vencimiento,
        'dias_credito' => $data['dias_credito'] ?? $cartera->dias_credito,
        'observaciones' => $data['observaciones'] ?? $cartera->observaciones,
        'user_comercial_id' => $data['user_comercial_id'] ?? $cartera->user_comercial_id,
    ]);

    return $cartera;
}


public function find($id)
{
    return GestionCartera::with('cliente', 'comercial', 'pagos')->findOrFail($id);  

}

public function cancelarDeuda($id)
{
    $cartera = GestionCartera::findOrFail($id);

    DB::transaction(function () use ($cartera) {

        $cartera->update([
            'saldo_pendiente' => 0,
            'estado' => 'cancelado'
        ]);

        // Opcional: registrar motivo
        $cartera->pagos()->create([
            'valor_pago' => $cartera->saldo_pendiente,
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
}