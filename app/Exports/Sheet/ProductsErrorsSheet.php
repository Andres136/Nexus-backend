<?php

namespace App\Exports\Sheet;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsErrorsSheet implements FromCollection, WithHeadings
{
    protected array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
    }

    public function collection()
    {
        return collect($this->errors)->map(function ($error) {
            return [
                'Fila Excel' => $error['row'],
                'Error' => $error['error'],
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Fila Excel',
            'Error',
        ];
    }
}