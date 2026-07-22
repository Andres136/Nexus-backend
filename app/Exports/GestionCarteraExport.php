<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class GestionCarteraExport implements FromCollection, WithHeadings, WithEvents
{
    protected $data;
    protected $headings;
    protected $empresaNombre;
    protected $logoPath;

    public function __construct(Collection $data, array $headings, $empresaNombre = null, $logoPath = null)
    {
        $this->data = $data;
        $this->headings = $headings;
        $this->empresaNombre = $empresaNombre;
        $this->logoPath = $logoPath;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                if (!$this->empresaNombre && !$this->logoPath) {
                    return;
                }

                $sheet = $event->sheet->getDelegate();
                $ultimaColumna = $sheet->getHighestColumn();
                $indiceUltimaColumna = Coordinate::columnIndexFromString($ultimaColumna);

                // Deja la última columna libre para el logo; el título ocupa el resto.
                $indiceColumnaTitulo = max(1, $indiceUltimaColumna - 1);
                $columnaTitulo = Coordinate::stringFromColumnIndex($indiceColumnaTitulo);

                // Reserva 3 filas arriba del encabezado para el nombre y el logo de la empresa.
                $sheet->insertNewRowBefore(1, 3);

                if ($this->empresaNombre) {
                    $sheet->setCellValue('A1', $this->empresaNombre);
                    $sheet->mergeCells("A1:{$columnaTitulo}1");
                    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                    $sheet->setCellValue('A2', 'Cartera - Generado: ' . now()->format('Y-m-d H:i'));
                    $sheet->mergeCells("A2:{$columnaTitulo}2");
                    $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);
                }

                if ($this->logoPath) {
                    $drawing = new Drawing();
                    $drawing->setName('Logo');
                    $drawing->setPath($this->logoPath);
                    $drawing->setHeight(60);
                    $drawing->setCoordinates("{$ultimaColumna}1");
                    $drawing->setOffsetX(5);
                    $drawing->setOffsetY(5);
                    $drawing->setWorksheet($sheet);
                }

                $sheet->getRowDimension(1)->setRowHeight(45);
            },
        ];
    }
}
