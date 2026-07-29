<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class PreliquidacionLoteExport implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize, WithColumnFormatting
{
    private const HOUR_COLUMNS = ['E', 'F', 'G', 'H', 'I'];

    private const MONEY_COLUMNS = ['J', 'K', 'L', 'M', 'O', 'R', 'T', 'U', 'V', 'W'];

    public function __construct(
        private readonly Collection $filas,
        private readonly array $headings,
        private readonly array $totales,
        private readonly string $periodoInicio,
        private readonly string $periodoFin,
        private readonly ?string $empresaNombre = null,
        private readonly ?string $logoPath = null,
    ) {}

    public function collection()
    {
        return $this->filas;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function columnFormats(): array
    {
        $formats = [];

        foreach (self::HOUR_COLUMNS as $column) {
            $formats[$column] = '0.00';
        }

        foreach (self::MONEY_COLUMNS as $column) {
            $formats[$column] = '"$"#,##0.00';
        }

        return $formats;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = Coordinate::stringFromColumnIndex(count($this->headings));

                // Reserva 3 filas arriba del encabezado para el título y el logo de la empresa.
                $sheet->insertNewRowBefore(1, 3);
                $headerRow = 4;
                $lastDataRow = $headerRow + $this->filas->count();
                $totalRow = $lastDataRow + 1;

                $tituloColumna = $this->logoPath
                    ? Coordinate::stringFromColumnIndex(max(1, count($this->headings) - 1))
                    : $lastColumn;

                $sheet->setCellValue('A1', $this->empresaNombre
                    ? "Preliquidación masiva de nómina — {$this->empresaNombre}"
                    : 'Preliquidación masiva de nómina');
                $sheet->mergeCells("A1:{$tituloColumna}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => '111827']],
                ]);

                $sheet->setCellValue('A2', "Período: {$this->periodoInicio} a {$this->periodoFin} · Generado: ".now()->format('Y-m-d H:i'));
                $sheet->mergeCells("A2:{$tituloColumna}2");
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '4B5563']],
                ]);

                $sheet->setCellValue('A3', "{$this->totales['empleados_calculados']} empleados calculados · Neto total: \$".number_format($this->totales['salario_neto'], 0, ',', '.'));
                $sheet->mergeCells("A3:{$tituloColumna}3");
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '065F46']],
                ]);

                if ($this->logoPath) {
                    $drawing = new Drawing();
                    $drawing->setName('Logo');
                    $drawing->setPath($this->logoPath);
                    $drawing->setHeight(60);
                    $drawing->setCoordinates("{$lastColumn}1");
                    $drawing->setOffsetX(5);
                    $drawing->setOffsetY(5);
                    $drawing->setWorksheet($sheet);
                }

                $sheet->getRowDimension(1)->setRowHeight(22);
                $sheet->getRowDimension(2)->setRowHeight(16);
                $sheet->getRowDimension(3)->setRowHeight(18);
                $sheet->getRowDimension($headerRow)->setRowHeight(30);

                $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2937']],
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                if ($this->filas->isNotEmpty()) {
                    $sheet->getStyle("A".($headerRow + 1).":{$lastColumn}{$lastDataRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'E5E7EB'],
                            ],
                        ],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);

                    $sheet->setCellValue("A{$totalRow}", 'TOTAL');
                    $sheet->setCellValue("F{$totalRow}", $this->totales['horas_extras_diurnas']);
                    $sheet->setCellValue("G{$totalRow}", $this->totales['horas_extras_nocturnas']);
                    $sheet->setCellValue("H{$totalRow}", $this->totales['horas_festivas']);
                    $sheet->setCellValue("I{$totalRow}", $this->totales['horas_nocturnas_festivas']);
                    $sheet->setCellValue("J{$totalRow}", $this->totales['valor_horas_extras_diurnas']);
                    $sheet->setCellValue("K{$totalRow}", $this->totales['valor_horas_extras_nocturnas']);
                    $sheet->setCellValue("L{$totalRow}", $this->totales['valor_horas_festivas']);
                    $sheet->setCellValue("M{$totalRow}", $this->totales['valor_horas_nocturnas_festivas']);
                    $sheet->setCellValue("N{$totalRow}", $this->totales['minutos_tardanza']);
                    $sheet->setCellValue("O{$totalRow}", $this->totales['valor_tardanzas']);
                    $sheet->setCellValue("Q{$totalRow}", $this->totales['minutos_permisos_no_remunerados']);
                    $sheet->setCellValue("R{$totalRow}", $this->totales['valor_permisos_no_remunerados']);
                    $sheet->setCellValue("U{$totalRow}", $this->totales['total_devengado']);
                    $sheet->setCellValue("V{$totalRow}", $this->totales['total_deducciones']);
                    $sheet->setCellValue("W{$totalRow}", $this->totales['salario_neto']);

                    foreach (self::HOUR_COLUMNS as $column) {
                        $sheet->getStyle("{$column}{$totalRow}")->getNumberFormat()->setFormatCode('0.00');
                    }
                    foreach (self::MONEY_COLUMNS as $column) {
                        $sheet->getStyle("{$column}{$totalRow}")->getNumberFormat()->setFormatCode('"$"#,##0.00');
                    }

                    $sheet->getStyle("A{$totalRow}:{$lastColumn}{$totalRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ECFDF5']],
                        'font' => ['bold' => true, 'color' => ['rgb' => '065F46']],
                    ]);

                    $sheet->getStyle("E{$headerRow}:{$lastColumn}{$totalRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    $sheet->freezePane("A".($headerRow + 1));
                    $sheet->setAutoFilter("A{$headerRow}:{$lastColumn}{$lastDataRow}");
                }

                $sheet->getColumnDimension('A')->setWidth(26);
                $sheet->getColumnDimension('B')->setWidth(28);
            },
        ];
    }
}
