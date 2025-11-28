<?php

namespace App\Exports;

use App\Models\Crm\product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;

class PlantillaProductosExport implements FromCollection, WithHeadings
{
    use Exportable;

    public function collection()
    {
        return product::select('id', 'code', 'name', 'description')
            ->orderBy('name')
            ->get()
            ->map(function ($prod) {
                return [ 
                    'Código'            => $prod->code,
                    'Nombre Producto'   => $prod->name,
                    'Descripción'       => $prod->description,
                    'Stock (Llenar)',
                       'precio'            => '',   // opcional
                    'min_stock'         => '',   // opcional
                    'max_stock'         => '',   // opcional
                    'fecha_vencimiento' => '',   // opciona
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Código',
            'Nombre Producto',
            'Descripción',
            'Stock',
            'Precio',
            'min_stock',
            'max_stock',
            'fecha_vencimiento'
        ];
    }
}
