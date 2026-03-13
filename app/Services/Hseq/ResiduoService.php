<?php

namespace App\Services\Hseq;

use App\Models\Hseq\Residuo;
use App\Models\User;

class ResiduoService
{
    public function create(array $data)
    {
        return Residuo::create($data);
    }


    public function find($id)
    {
        return Residuo::findOrFail($id);
    }

    public function update($id, array $data)
    {
        $residuo = $this->find($id);
        $residuo->update($data);
        return $residuo;
    }

    public function delete($id)
    {
        $residuo = $this->find($id);
        $residuo->delete();
        return true;
    } 

      public function timeline(array $filters)
    {
               $sedeId = $filters['sede_id'] ?? null;
        $anio = $filters['anio'] ?? date('Y');

        $query = Residuo::query();

        if ($sedeId) {
            $query->where('sede_id', $sedeId);
        }

        // consulta agrupada por mes
        $residuos = $query
            ->selectRaw('
                MONTH(fecha) as mes_num,
                MONTHNAME(fecha) as mes,
                SUM(cantidad) as total
            ')
            ->whereYear('fecha', $anio)
            ->groupBy('mes_num', 'mes')
            ->orderBy('mes_num')
            ->get();

        // contar usuarios de la sede
        $usuarios = 0;

        if ($sedeId) {
            $usuarios = User::where('sede_id', $sedeId)->count();
        }

        // construir timeline
        $timeline = $residuos->map(function ($item) use ($usuarios) {

            $perCapita = null;

            if ($usuarios > 0) {
                $perCapita = round($item->total / $usuarios, 2);
            }

            return [
                'mes_num' => $item->mes_num,
                'mes' => $item->mes,
                'total_residuos' => $item->total,
                'usuarios' => $usuarios,
                'residuo_per_capita' => $perCapita
            ];
        });

        return [
            'anio' => $anio,
            'sede_id' => $sedeId,
            'timeline' => $timeline
        ];

    }
}