<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Milon\Barcode\DNS1D;

class PdfEtiquetas
{
    public static function generar($productos)
    {
        $barcode = new DNS1D();
        $barcode->setStorPath(storage_path('framework/barcodes'));

        $data = $productos->map(function ($p) use ($barcode) {
            return [
                'name' => $p->name,
                'code' => $p->code,
                // SOLO base64
                'barcode' => $barcode->getBarcodePNG($p->code, 'C39'),
            ];
        });

        return Pdf::loadView('pdf.etiquetas', [
            'productos' => $data
        ])->output();
    }
}
