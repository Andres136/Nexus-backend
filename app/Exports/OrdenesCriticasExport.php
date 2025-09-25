<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrdenesCriticasExport implements FromCollection, WithHeadings, WithMapping
{
    protected $vencidas;
    protected $fecha;

    public function __construct($vencidas, $faltantes, $hoy, $fecha)
    {
        $this->vencidas = $vencidas;
        $this->fecha = $fecha;
    }

    public function collection()
    {
        $data = collect();

        foreach ($this->vencidas as $orden) {
            foreach ($orden->detalles as $detalle) {
                $data->push((object)[
                    'orden' => $orden,
                    'detalle' => $detalle,
                    'tipo' => 'VENCIDA',
                    'prioridad' => 1
                ]);
            }
        }

        return $data->sortBy(function ($item) {
            return \Carbon\Carbon::parse($item->orden->fecha_entrega);
        })->values();
    }

    public function headings(): array
    {
        return [
            'Estado',
            'OC #',
            'Cliente',
            'Fecha Entrega',
            'Fecha Despacho',
            'Referencia',
            'Descripción',
            'Cantidad',
            'Enviada',
            'Faltantes',
        ];
    }

    public function map($row): array
    {
        $orden = $row->orden;
        $detalle = $row->detalle;

        return [
            $row->tipo, // Estado: VENCIDA
            $orden->id, // OC #
            optional($orden->cliente)->nombre ?? 'N/A', // Cliente
            \Carbon\Carbon::parse($orden->fecha_entrega)->format('Y-m-d'), // Fecha Entrega
            $orden->fecha_despacho ?? $orden->updated_at, // Fecha Despacho
            $detalle->cliente_clb ?? 'N/A', // Referencia
            $detalle->descripcion ?? '', // Descripción
            $detalle->cantidad ?? 0, // Cantidad
            $detalle->cantidad_enviada ?? 0, // Enviada
            $detalle->faltantes ?? 0, // Faltantes
        ];
    }
}
