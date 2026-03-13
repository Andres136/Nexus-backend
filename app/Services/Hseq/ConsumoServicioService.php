<?php

namespace App\Services\Hseq;

use App\Models\Hseq\ConsumoServicio;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConsumoServicioService
{
 public function create(array $data)
    {
        // contar personas en la sede
        $personas = User::where('sede_id', $data['sede_id'])->count();

        // calcular consumo per capita
        $consumoPercapita = null;

        if ($personas > 0) {
            $consumoPercapita = $data['consumo'] / $personas;
        }

        // crear registro
        return ConsumoServicio::create([
            'sede_id' => $data['sede_id'],
            'tipo_servicio_id' => $data['tipo_servicio_id'],
            'consumo' => $data['consumo'],
            'fecha_consumo' => $data['fecha_consumo'],
            'fecha_pago' => $data['fecha_pago'] ?? null,
            'valor_factura' => $data['valor_factura'] ?? null,
            'estado' => $data['estado'] ?? 'pendiente',
            'consumo_percapita' => $consumoPercapita
        ]);   
    }


    //Resto de métodos para find, update, delete, etc.



public function find($id)
    {
        return ConsumoServicio::findOrFail($id);
    }

    public function update($id, array $data)
    {
        $consumoServicio = $this->find($id);
        $consumoServicio->update($data);
        return $consumoServicio;
    }

    public function delete($id)
    {
        $consumoServicio = $this->find($id);
        return $consumoServicio->delete();
    }

    public function estadisticasAnuales($year, $tipoServicioId = null)
{
    $query = ConsumoServicio::select(
        DB::raw('MONTH(fecha_consumo) as mes'),
        DB::raw('SUM(consumo) as total_consumo')
    )
    ->whereYear('fecha_consumo', $year);

    if ($tipoServicioId) {
        $query->where('tipo_servicio_id', $tipoServicioId);
    }

    $data = $query
        ->groupBy(DB::raw('MONTH(fecha_consumo)'))
        ->orderBy('mes')
        ->get();

    $totalAnual = $data->sum('total_consumo');

    return $data->map(function ($item) use ($totalAnual) {
        $item->porcentaje = $totalAnual > 0
            ? round(($item->total_consumo / $totalAnual) * 100, 2)
            : 0;

        return $item;
    });
}
}