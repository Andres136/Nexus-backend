<?php
namespace App\Exports\Sheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsCreatedSheet implements FromCollection, WithHeadings
{
    protected array $products;

    public function __construct(array $products)
    {
        $this->products = $products;
    }

    public function collection()
    {
        return collect($this->products)->map(function ($product) {
            return [
                'ID' => $product->id,
                'Nombre' => $product->name,
                'Descripción' => $product->description,
                'Código' => $product->code,
                'Categoría ID' => $product->categoria_id,
                'Creado en' => $product->created_at,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Descripción',
            'Código',
            'Categoría ID',
            'Creado en',
        ];
    }
}
