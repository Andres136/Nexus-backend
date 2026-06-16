<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class NominaPucExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithTitle
{
    private const HEADINGS = [
        'Nomina UUID',
        'Empleado',
        'Documento',
        'Periodo inicio',
        'Periodo fin',
        'Concepto',
        'Codigo',
        'Cuenta PUC',
        'Nombre cuenta',
        'Naturaleza',
        'Valor',
    ];

    public function __construct(
        private readonly Collection $rows
    ) {}

    public function array(): array
    {
        $periodoInicio = $this->rows->pluck('periodo_inicio')->filter()->unique()->sort()->first() ?? '-';
        $periodoFin = $this->rows->pluck('periodo_fin')->filter()->unique()->sort()->last() ?? '-';

        $data = [
            ['PUC CONTABLE DE NOMINA'],
            ["Periodo: {$periodoInicio} a {$periodoFin}"],
            [
                'Documento nomina',
                '',
                '',
                'Periodo',
                '',
                'Concepto contable',
                '',
                'Cuenta PUC',
                '',
                '',
                'Valor',
            ],
            self::HEADINGS,
        ];

        foreach ($this->rows as $row) {
            $data[] = [
            $row['nomina_uuid'],
            $row['empleado'],
            $row['documento'],
            $row['periodo_inicio'],
            $row['periodo_fin'],
            $row['concepto'],
            $row['codigo'],
            $row['cuenta_puc'],
            $row['cuenta_nombre'],
            $row['naturaleza'],
            $row['valor'],
            ];
        }

        $data[] = $this->totalRow();

        return $data;
    }

    public function columnFormats(): array
    {
        return [
            'D:E' => NumberFormat::FORMAT_TEXT,
            'K' => '"$"#,##0.00',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $this->rows->count() + 5;
                $lastColumn = Coordinate::stringFromColumnIndex(count(self::HEADINGS));

                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->mergeCells("A2:{$lastColumn}2");
                $sheet->mergeCells('A3:C3');
                $sheet->mergeCells('D3:E3');
                $sheet->mergeCells('F3:G3');
                $sheet->mergeCells('H3:J3');

                $sheet->freezePane('A5');
                $sheet->setAutoFilter("A4:{$lastColumn}{$lastRow}");

                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '0F172A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '475569']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle("A3:{$lastColumn}3")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCECF8']],
                    'font' => ['bold' => true, 'color' => ['rgb' => '075985']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $sheet->getStyle("A5:{$lastColumn}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E5E7EB'],
                        ],
                    ],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $sheet->getStyle("A{$lastRow}:{$lastColumn}{$lastRow}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ECFDF5']],
                    'font' => ['bold' => true, 'color' => ['rgb' => '065F46']],
                ]);

                $sheet->getStyle("K5:K{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->getColumnDimension('A')->setWidth(42);
                $sheet->getColumnDimension('B')->setWidth(28);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(16);
                $sheet->getColumnDimension('E')->setWidth(16);
                $sheet->getColumnDimension('F')->setWidth(26);
                $sheet->getColumnDimension('G')->setWidth(20);
                $sheet->getColumnDimension('H')->setWidth(16);
                $sheet->getColumnDimension('I')->setWidth(28);
                $sheet->getColumnDimension('J')->setWidth(14);
                $sheet->getColumnDimension('K')->setWidth(16);
                $sheet->getRowDimension(4)->setRowHeight(34);
            },
        ];
    }

    public function title(): string
    {
        return 'PUC nomina';
    }

    private function totalRow(): array
    {
        $row = array_fill(0, count(self::HEADINGS), '');
        $row[0] = 'TOTAL';
        $row[1] = $this->rows->pluck('nomina_uuid')->unique()->count().' nominas';
        $row[5] = $this->rows->count().' asientos';
        $row[10] = round((float) $this->rows->sum('valor'), 2);

        return $row;
    }
}
