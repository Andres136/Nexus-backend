<?php

use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PdfEtiquetas
{
    public static function generar($productos)
    {
        $productos = collect($productos);

        $data = $productos->map(function ($p) {

            $url = url('/scan/' . $p->code);

            // ✅ QR SVG (NO usa Imagick)
            $qrSvg = QrCode::format('svg')
                ->size(120)
                ->margin(1)
                ->generate($url);

            return [
                'name'   => $p->name ?? 'SIN NOMBRE',
                'code'   => (string) $p->code,
                'qr_svg' => $qrSvg,
            ];
        });

        return Pdf::loadView('pdf.etiquetas', [
            'productos' => $data
        ])
        ->setPaper([0, 0, 292, 142]) // etiqueta
        ->output();
    }
}