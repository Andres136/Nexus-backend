<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CosteoUtilidadExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        $this->data = collect($data);
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Producto',
            'Descripción',
            'KG Vendidos',
            'Ingreso sin IVA',
            'Costo Promedio KG sin IVA',
            'Costo Total sin IVA',
            'KG Comprados',
            'Costo Total Comprado sin IVA',
            'Utilidad sin IVA',
            'Margen %',
        ];
    }

    public function map($row): array
    {
        return [
            $row['Producto'],
            $row['Descripción'],
            round((float) $row['KG Vendidos'], 2),
            round((float) $row['Ingreso sin IVA'], 2),
            round((float) $row['Costo Promedio KG sin IVA'], 2),
            round((float) $row['Costo Total sin IVA'], 2),
            round((float) $row['KG Comprados'], 2),
            round((float) $row['Costo Total Comprado sin IVA'], 2),
            round((float) $row['Utilidad sin IVA'], 2),
            round((float) $row['Margen %'], 2),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'J' => '0.00"%"',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);

        return [];
    }
}
