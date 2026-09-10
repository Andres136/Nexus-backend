<?php

namespace App\Exports;

use App\Models\Nomina\HoraExtra;
use Carbon\Carbon;
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

class HorasExtrasExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithTitle
{
    private const HEADINGS = [
        'Empleado',
        'Documento',
        'Cargo',
        'Sede',
        'Fecha',
        'Día',
        'Hora inicio',
        'Hora fin',
        'Horas aprobadas',
        'Horas reconocidas',
        'Tipo',
        'Estado',
        'Origen',
        'Kiosko',
        'Motivo',
        'Solicitada por',
        'Fecha solicitud',
        'Gestionada por',
        'Fecha gestión',
        'Observación gestión',
    ];

    private const DIAS = ['Sun' => 'Dom', 'Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Mié', 'Thu' => 'Jue', 'Fri' => 'Vie', 'Sat' => 'Sáb'];

    private const TIPOS = [
        'diurna' => 'Diurna',
        'nocturna' => 'Nocturna',
        'festiva' => 'Festiva',
        'nocturna_festiva' => 'Nocturna festiva',
    ];

    /**
     * @param  Collection<int, HoraExtra>  $registros
     * @param  array<int, int|null>  $minutosReconocidos  [hora_extra_id => minutos] (null = no aprobada)
     */
    public function __construct(
        private readonly Collection $registros,
        private readonly string $resumenFiltros,
        private readonly array $minutosReconocidos = []
    ) {}

    public function array(): array
    {
        $rows = [
            ['HORAS EXTRAS'],
            [$this->resumenFiltros.'  ·  Generado: '.now()->format('Y-m-d H:i')],
            self::HEADINGS,
        ];

        foreach ($this->registros as $h) {
            $contrato = $h->empleado?->contratacionActivaNomina;
            $recMin = $this->minutosReconocidos[$h->id] ?? null;
            $rows[] = [
                $h->empleado?->nombre_completo ?? '—',
                $contrato?->numero_documento ?? $h->empleado?->email ?? '—',
                $contrato?->cargo ?? '—',
                $h->sede?->nombre ?? '—',
                optional($h->fecha)->format('Y-m-d'),
                $h->fecha ? (self::DIAS[$h->fecha->format('D')] ?? $h->fecha->format('D')) : '',
                $this->hora($h->hora_inicio),
                $this->hora($h->hora_fin),
                round((float) $h->horas, 2),
                $recMin === null ? '—' : round($recMin / 60, 2),
                self::TIPOS[$h->tipo] ?? ($h->tipo ?? '—'),
                ucfirst($h->status ?? '—'),
                $h->origen === 'kiosko' ? 'Kiosko' : 'Administrativo',
                $h->kiosko?->name ?? $h->kiosko?->code ?? '—',
                $h->motivo ?? '—',
                $h->solicitante?->nombre_completo ?? '—',
                optional($h->created_at)->format('Y-m-d H:i'),
                $h->supervisor?->nombre_completo ?? '—',
                optional($h->fecha_gestion)->format('Y-m-d H:i'),
                $h->observacion_gestion ?? '',
            ];
        }

        $rows[] = $this->totalRow();

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_DATE_YYYYMMDD,
            'G' => NumberFormat::FORMAT_TEXT,
            'H' => NumberFormat::FORMAT_TEXT,
            'I' => '0.00',
            'J' => '0.00',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = Coordinate::stringFromColumnIndex(count(self::HEADINGS));
                $headerRow = 3;
                $firstData = 4;
                $totalRow = $this->registros->count() + $firstData;

                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->mergeCells("A2:{$lastColumn}2");
                $sheet->freezePane('A4');
                $sheet->setAutoFilter("A{$headerRow}:{$lastColumn}".max($totalRow - 1, $headerRow));

                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '111827']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
                    'font' => ['size' => 10, 'color' => ['rgb' => '6B7280']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2937']],
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);
                $sheet->getRowDimension($headerRow)->setRowHeight(28);

                if ($this->registros->isNotEmpty()) {
                    $sheet->getStyle("A{$firstData}:{$lastColumn}{$totalRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getStyle("A{$totalRow}:{$lastColumn}{$totalRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ECFDF5']],
                        'font' => ['bold' => true, 'color' => ['rgb' => '065F46']],
                    ]);
                    $sheet->getStyle("I{$firstData}:J{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
            },
        ];
    }

    public function title(): string
    {
        return 'Horas extras';
    }

    private function totalRow(): array
    {
        $row = array_fill(0, \count(self::HEADINGS), '');
        $row[0] = 'TOTAL ('.$this->registros->count().' registros)';
        $row[8] = round((float) $this->registros->sum(fn (HoraExtra $h) => (float) $h->horas), 2);
        $row[9] = round(array_sum(array_map(
            fn ($m) => $m === null ? 0 : $m,
            $this->minutosReconocidos
        )) / 60, 2);

        return $row;
    }

    private function hora(?string $hora): string
    {
        if (! $hora) {
            return '';
        }

        try {
            return Carbon::parse($hora)->format('H:i');
        } catch (\Throwable) {
            return $hora;
        }
    }
}
