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
    $query = GestionCartera::query()->with('cliente', 'comercial');

    $user = auth()->user();

    // Restricción por rol
    if ($user->role_id == 7) { // Comercial
        $query->where('user_comercial_id', $user->id);
    }

    // Filtros opcionales
    if (!empty($filtros['cliente_id'])) {
        $query->where('cliente_id', $filtros['cliente_id']);
    }

    if (!empty($filtros['user_comercial_id']) && $user->role_id != 7) {
        $query->where('user_comercial_id', $filtros['user_comercial_id']);
    }

    if (!empty($filtros['estado'])) {
        $query->where('estado', $filtros['estado']);
    }

    if (!empty($filtros['numero_factura'])) {
        $query->where('numero_factura', 'like', '%' . $filtros['numero_factura'] . '%');
    }

    // Orden de cartera
    $query->orderByRaw("
        CASE
            WHEN estado = 'pendiente' AND fecha_vencimiento < CURDATE() THEN 0
            WHEN estado = 'pendiente' THEN 1
            ELSE 2
        END
    ");

    $query->orderBy('fecha_vencimiento', 'asc');

    return $query->paginate($filtros['per_page'] ?? 20);
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
}