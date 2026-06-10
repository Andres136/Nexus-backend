<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class NominaPucExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(
        private readonly array $data
    ) {}

    public function title(): string
    {
        return 'PUC Nomina';
    }

    public function array(): array
    {
        $rows = [
            ['Comprobante PUC de Nómina'],
            [
                'Período inicio',
                Carbon::parse($this->data['periodo_inicio'])->format('Y-m-d'),
                'Período fin',
                Carbon::parse($this->data['periodo_fin'])->format('Y-m-d'),
                'Generado',
                now()->format('Y-m-d H:i'),
                '',
                '',
            ],
            [],
            ['Cuenta', 'Concepto', 'Tercero', 'Centro de costo', 'Débito', 'Crédito', 'Tipo movimiento', 'Período'],
        ];

        foreach ($this->data['lineas'] as $linea) {
            $rows[] = [
                $linea['cuenta'],
                $linea['concepto'],
                $linea['tercero'],
                $linea['centro_costo'],
                (float) $linea['debito'],
                (float) $linea['credito'],
                ((float) $linea['debito']) > 0 ? 'Débito' : 'Crédito',
                $this->data['periodo_inicio'].' a '.$this->data['periodo_fin'],
            ];
        }

        $rows[] = [];
        $rows[] = [
            '',
            '',
            '',
            'Totales',
            (float) ($this->data['totales']['debito'] ?? 0),
            (float) ($this->data['totales']['credito'] ?? 0),
            '',
            '',
        ];

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = count($this->array());
                $headerRow = 4;
                $totalRow = $lastRow;

                $sheet->mergeCells('A1:H1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '312E81']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A2:H2')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '374151']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2FF']],
                ]);

                $sheet->getStyle("A{$headerRow}:H{$headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '111827']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
                ]);

                $sheet->getStyle("A{$headerRow}:H{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
                ]);

                $sheet->getStyle("D{$totalRow}:F{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCFCE7']],
                ]);

                $sheet->getStyle("E5:F{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);

                $sheet->getStyle("E5:F{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->setAutoFilter("A{$headerRow}:H{$lastRow}");
                $sheet->freezePane('A5');
            },
        ];
    }
}
