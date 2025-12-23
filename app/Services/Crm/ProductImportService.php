<?php

namespace App\Services\Crm;

use App\Models\Crm\categoria;
use App\Models\Crm\product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;


class ProductImportService
{
  public function importFromExcel($file, int $categoryId): array
    {



       
        // ✅ LECTURA CORRECTA DEL EXCEL
        $rows = Excel::toCollection(null, $file)->first()->toArray();
        unset($rows[0]); // encabezados

        $created = [];
        $errors = [];

        // ✅ CATEGORÍA UNA SOLA VEZ
        $categoria = categoria::find($categoryId);

        if (!$categoria) {
            return [
                'created' => [],
                'errors' => [['row' => '-', 'error' => 'Categoría no existe']]
            ];
        }

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {

                if (empty($row[0])) {
                    $errors[] = [
                        'row' => $index + 1,
                        'error' => 'Nombre del producto obligatorio'
                    ];
                    continue;
                }

                $prefix = strtoupper(Str::substr($categoria->nombre, 0, 2));

                $lastProduct = product::where('categoria_id', $categoria->id)
                    ->where('code', 'like', $prefix . '-%')
                    ->orderBy('id', 'desc')
                    ->lockForUpdate()
                    ->first();

                $next = $lastProduct
                    ? intval(substr($lastProduct->code, 3)) + 1
                    : 1;

                $code = $prefix . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);

                $created[] = Product::create([
                    'name' => $row[0],
                    'description' => $row[1] ?? null,
                    'categoria_id' => $categoria->id,
                    'code' => $code,
                ]);
            }

            DB::commit();

            return compact('created', 'errors');

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}