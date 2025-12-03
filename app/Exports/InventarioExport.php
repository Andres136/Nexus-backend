<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InventarioExport implements FromCollection, WithHeadings
{
    protected Collection $data;

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data->map(function ($inv) {
            return [
                'Producto'          => $inv->producto->name ?? '',
                'Código'            => $inv->producto->code ?? '',
                'Empresa'           => $inv->empresa->nombre ?? '',
                'Sede'              => $inv->sede->nombre ?? '',
                'Bodega'            => $inv->bodega->nombre ?? '',
                'Stock'             => $inv->stock,
                'Stock mínimo'      => $inv->stock_minimo,
                'Stock máximo'      => $inv->stock_maximo,
                'Valor unitario'    => $inv->valor_unitario,
                'Valor total'       => $inv->valor_total,
                'Último movimiento' => optional($inv->ultimo_movimiento)->format('Y-m-d'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Producto',
            'Código',
            'Empresa',
            'Sede',
            'Bodega',
            'Stock',
            'Stock mínimo',
            'Stock máximo',
            'Valor unitario',
            'Valor total',
            'Último movimiento'
        ];
    }
}
