<?php
namespace App\Services;
use Barryvdh\DomPDF\Facade\Pdf;
use Milon\Barcode\DNS1D;

class PdfEtiquetaService
{
 public static function generar($productos)
    {
       
        $barcode = new DNS1D();

        $data = $productos->map(function ($p) use ($barcode) {

            // 🔴 SIEMPRE URL COMPLETA
            $url = url('/s/' . $p->code);

            return [
                'name' => $p->name,
                'code' => (string) $p->code,
                'barcode' => $barcode->getBarcodePNG(
                    $url,
                    'C128',
                    1,   // ancho barras
                    80   // alto barras
                ),
            ];
        });

        return Pdf::loadView('pdf.etiquetas', [
            'productos' => $data
        ])
        ->setPaper([0, 0, 292, 142], 'portrait')
         ->output();
    }
}
