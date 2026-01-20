<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Milon\Barcode\DNS1D;

class PdfEtiquetas
{
 public static function generar($productos)
    {
       
        $barcode = new DNS1D();

        $data = $productos->map(function ($p) use ($barcode) {

            // 🔴 SIEMPRE URL COMPLETA
            $url = url('/scan/' . $p->code);

            return [
                'name' => $p->name,
                'code' => (string) $p->code,
                'barcode' => $barcode->getBarcodePNG(
                    $url,
                    'C128',
                    1,   // ancho barras
                    70   // alto barras
                ),
            ];
        });

        return Pdf::loadView('pdf.etiquetas', [
            'productos' => $data
        ])
        ->setPaper([0, 0, 136, 71]) // 48mm x 25mm aprox
        ->output();
    }
}
