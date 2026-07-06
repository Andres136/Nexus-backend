<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class CosteoUtilidadExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, WithEvents
{
    protected $data;
    protected $empresaNombre;
    protected $logoPath;

    public function __construct($data, $empresaNombre = null, $logoPath = null)
    {
        $this->data = collect($data);
        $this->empresaNombre = $empresaNombre;
        $this->logoPath = $logoPath;
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Reserva 3 filas arriba del encabezado para el título y el logo.
                $sheet->insertNewRowBefore(1, 3);

                $titulo = $this->empresaNombre ?: 'Costeo de Utilidad';

                $sheet->setCellValue('A1', $titulo);
                $sheet->mergeCells('A1:H1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $sheet->setCellValue(
                    'A2',
                    'Costeo de Utilidad - Generado: ' . now()->format('Y-m-d H:i')
                );
                $sheet->mergeCells('A2:H2');
                $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);

                if ($this->logoPath) {
                    $drawing = new Drawing();
                    $drawing->setName('Logo');
                    $drawing->setPath($this->logoPath);
                    $drawing->setHeight(60);
                    $drawing->setCoordinates('I1');
                    $drawing->setOffsetX(5);
                    $drawing->setOffsetY(5);
                    $drawing->setWorksheet($sheet);
                }

                $sheet->getStyle('A4:J4')->getFont()->setBold(true);
            },
        ];
    }
}
