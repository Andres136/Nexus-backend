<?php

namespace App\Exports;

use App\Models\Crm\product;
use Maatwebsite\Excel\Concerns\FromCollection;

class CosteoUtilidadExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */

       protected $data;

    public function __construct($data)
    {
        $this->data = collect($data);
    }

    public function collection()
    {
        return $this->data;
    }
}
