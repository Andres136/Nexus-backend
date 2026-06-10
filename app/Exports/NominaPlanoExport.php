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

class NominaPlanoExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithTitle
{
    private const HEADINGS = [
        'Tipo documento',
        'Documento',
        'Empleado',
        'Correo',
        'Cargo',
        'Periodo inicio',
        'Periodo fin',
        'Horas normales',
        'Horas extra diurnas',
        'Horas extra nocturnas',
        'Horas festivas',
        'Horas nocturnas festivas',
        'Salario base devengado',
        'Auxilio transporte',
        'Comisiones',
        'Novedades retroactivas',
        'Valor horas normales',
        'Valor extra diurna',
        'Valor extra nocturna',
        'Valor horas festivas',
        'Valor nocturnas festivas',
        'Total devengado',
        'Salud',
        'Pension',
        'Otros descuentos',
        'Total deducciones',
        'Neto a pagar',
        'Fecha liquidacion',
    ];

    private const HOUR_COLUMNS = ['H', 'I', 'J', 'K', 'L'];

    private const MONEY_COLUMNS = ['M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA'];

    public function __construct(
        private readonly Collection $nominas,
        private readonly string $periodoInicio,
        private readonly string $periodoFin
    ) {}

    public function array(): array
    {
        $rows = [
            ['NOMINA LIQUIDADA'],
            ["Periodo: {$this->periodoInicio} a {$this->periodoFin}"],
            [
                'Identificacion',
                '',
                '',
                '',
                '',
                'Periodo',
                '',
                'Tiempo trabajado',
                '',
                '',
                '',
                '',
                'Devengos',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'Deducciones y neto',
                '',
                '',
                '',
                '',
                '',
                'Control',
            ],
            self::HEADINGS,
        ];

        foreach ($this->nominas as $nomina) {
            $rows[] = [
                $nomina->contratacion?->tipo_documento,
                $nomina->contratacion?->numero_documento,
                $nomina->empleado?->name,
                $nomina->empleado?->email,
                $nomina->contratacion?->cargo,
                $nomina->periodo_inicio?->format('Y-m-d'),
                $nomina->periodo_fin?->format('Y-m-d'),
                $this->number($nomina->horas_normales),
                $this->number($nomina->horas_extras_diurnas),
                $this->number($nomina->horas_extras_nocturnas),
                $this->number($nomina->horas_festivas),
                $this->number($nomina->horas_nocturnas_festivas),
                $this->number($nomina->salario_base_devengado),
                $this->number($nomina->auxilio_transporte),
                $this->number($nomina->total_comisiones),
                $this->number($nomina->total_novedades_retroactivas),
                $this->number($nomina->valor_horas_normales),
                $this->number($nomina->valor_horas_extras_diurnas),
                $this->number($nomina->valor_horas_extras_nocturnas),
                $this->number($nomina->valor_horas_festivas),
                $this->number($nomina->valor_horas_nocturnas_festivas),
                $this->number($nomina->total_devengado),
                $this->number($nomina->deduccion_salud),
                $this->number($nomina->deduccion_pension),
                $this->number($nomina->total_descuentos_adicionales),
                $this->number($nomina->total_deducciones),
                $this->number($nomina->salario_neto),
                $nomina->fecha_liquidacion?->format('Y-m-d H:i:s'),
            ];
        }

        $rows[] = $this->totalRow();

        return $rows;
    }

    public function columnFormats(): array
    {
        $formats = [
            'F:G' => NumberFormat::FORMAT_TEXT,
            'AB' => NumberFormat::FORMAT_TEXT,
        ];

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
                $lastRow = $this->nominas->count() + 5;
                $lastColumn = Coordinate::stringFromColumnIndex(count(self::HEADINGS));

                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->mergeCells("A2:{$lastColumn}2");
                $sheet->mergeCells('A3:E3');
                $sheet->mergeCells('F3:G3');
                $sheet->mergeCells('H3:L3');
                $sheet->mergeCells('M3:U3');
                $sheet->mergeCells('V3:AA3');

                $sheet->freezePane('A5');
                $sheet->setAutoFilter("A4:{$lastColumn}{$lastRow}");

                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '111827']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '4B5563']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle("A3:{$lastColumn}3")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0F2FE']],
                    'font' => ['bold' => true, 'color' => ['rgb' => '075985']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2937']],
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

                $sheet->getStyle("H5:AA{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->getRowDimension(4)->setRowHeight(36);
                $sheet->getColumnDimension('C')->setWidth(28);
                $sheet->getColumnDimension('D')->setWidth(30);
                $sheet->getColumnDimension('E')->setWidth(24);
                $sheet->getColumnDimension('AB')->setWidth(22);
            },
        ];
    }

    public function title(): string
    {
        return 'Nomina liquidada';
    }

    private function totalRow(): array
    {
        $row = array_fill(0, count(self::HEADINGS), '');
        $row[0] = 'TOTAL';
        $row[2] = $this->nominas->count().' empleados';

        $sumColumns = [
            7 => 'horas_normales',
            8 => 'horas_extras_diurnas',
            9 => 'horas_extras_nocturnas',
            10 => 'horas_festivas',
            11 => 'horas_nocturnas_festivas',
            12 => 'salario_base_devengado',
            13 => 'auxilio_transporte',
            14 => 'total_comisiones',
            15 => 'total_novedades_retroactivas',
            16 => 'valor_horas_normales',
            17 => 'valor_horas_extras_diurnas',
            18 => 'valor_horas_extras_nocturnas',
            19 => 'valor_horas_festivas',
            20 => 'valor_horas_nocturnas_festivas',
            21 => 'total_devengado',
            22 => 'deduccion_salud',
            23 => 'deduccion_pension',
            24 => 'total_descuentos_adicionales',
            25 => 'total_deducciones',
            26 => 'salario_neto',
        ];

        foreach ($sumColumns as $index => $column) {
            $row[$index] = $this->number($this->nominas->sum($column));
        }

        return $row;
    }

    private function number(mixed $value): float
    {
        return round((float) ($value ?? 0), 2);
    }
}
