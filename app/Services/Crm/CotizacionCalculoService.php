<?php

namespace App\Services\Crm;

class CotizacionCalculoService
{
    private const FACTOR_PULGADA = 0.393701;
    private const FACTOR_CONSTANTE = 302;

    public function calcular(array $item, float $iva = 19): array
    {
        $ancho = max(0, (float) ($item['ancho_cm'] ?? 0));
        $largo = max(0, (float) ($item['largo_cm'] ?? 0));
        $calibre = max(0, (float) ($item['calibre'] ?? 0));
        $precioKilo = max(0, (float) ($item['precio_total'] ?? 0));
        $cantidad = max(0, (float) ($item['cantidad'] ?? 0));
        $unitarioManual = max(0, (float) ($item['valor_unitario'] ?? 0));

        $pesoBolsa = 0;
        $numeroBolsas = 0;
        if ($ancho > 0 && $largo > 0 && $calibre > 0) {
            $anchoIn = $ancho * self::FACTOR_PULGADA;
            $largoIn = $largo * self::FACTOR_PULGADA;
            if ($ancho < 100) $anchoIn = ceil($anchoIn);
            if ($largo < 100) $largoIn = ceil($largoIn);
            $pesoBolsa = floor(($anchoIn * $largoIn * $calibre * self::FACTOR_CONSTANTE) / 10000);
            if ($pesoBolsa > 0) {
                $bolsas = 1000 / $pesoBolsa;
                $entero = floor($bolsas);
                $numeroBolsas = ($bolsas - $entero) >= 0.5 ? $entero + 1 : $entero;
            }
        }

        $valorUnitario = 0;
        if ($precioKilo > 0 && $numeroBolsas > 0) {
            $valorUnitario = ceil($precioKilo / $numeroBolsas);
        } elseif ($unitarioManual > 0) {
            $valorUnitario = round($unitarioManual, 2);
            if ($numeroBolsas === 0) {
                $numeroBolsas = 1;
                $precioKilo = $valorUnitario;
            }
        }

        $subtotal = round($valorUnitario * $cantidad, 2);

        return [
            'peso_bolsa' => $pesoBolsa,
            'numero_bolsas' => (int) $numeroBolsas,
            'precio_total' => round($precioKilo, 2),
            'valor_unitario' => round($valorUnitario, 2),
            'valor_paquete' => $subtotal,
            'valor_total' => round($subtotal * (1 + $iva / 100), 2),
        ];
    }
}
