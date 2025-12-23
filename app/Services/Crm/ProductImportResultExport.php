<?php


namespace App\Services\Crm;

use App\Exports\Sheet\ProductsCreatedSheet;
use App\Exports\Sheet\ProductsErrorsSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;


class ProductImportResultExport implements WithMultipleSheets
{
    protected array $created;
    protected array $errors;

    public function __construct(array $created, array $errors)
    {
        $this->created = $created;
        $this->errors = $errors;
    }

    public function sheets(): array
    {
        return [
            new ProductsCreatedSheet($this->created),
            new ProductsErrorsSheet($this->errors),
        ];
    }
}