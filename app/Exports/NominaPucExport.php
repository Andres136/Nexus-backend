<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class NominaPucExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function __construct(
        private readonly Collection $rows
    ) {}

    public function headings(): array
    {
        return [
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
    }

    public function array(): array
    {
        return $this->rows->map(fn ($row) => [
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
        ])->all();
    }
}
