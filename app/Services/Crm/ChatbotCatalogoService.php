<?php

namespace App\Services\Crm;

use App\Models\Crm\product;

class ChatbotCatalogoService
{
    private const LIMITE = 8;

    /**
     * Texto plano para devolver como resultado de la function call al modelo.
     * Solo expone nombre y descripción: nunca precio, stock, categoría ni
     * códigos internos (SIIGO, inventario), que son datos internos por sede.
     */
    public function consultarProductos(?string $categoriaNombre, ?string $busqueda): string
    {
        $query = product::query();

        if ($categoriaNombre) {
            $query->whereHas('categoria', fn ($q) => $q->where('nombre', 'LIKE', "%{$categoriaNombre}%"));
        }

        if ($busqueda) {
            // Busca por palabra suelta (no solo la frase completa) para que
            // consultas naturales del visitante ("bolsas para basura negras")
            // encuentren productos aunque el nombre no coincida literalmente.
            $palabras = array_filter(preg_split('/\s+/', trim($busqueda)));
            $query->where(function ($q) use ($palabras) {
                foreach ($palabras as $palabra) {
                    $q->orWhere('name', 'LIKE', "%{$palabra}%")
                        ->orWhere('description', 'LIKE', "%{$palabra}%");
                }
            });
        }

        $productos = $query->limit(self::LIMITE)->get();

        if ($productos->isEmpty()) {
            return 'No se encontraron productos que coincidan con esa búsqueda. Sugiere hablar con un asesor si necesita algo más específico.';
        }

        return $productos->map(function (product $p) {
            $linea = "- {$p->name}";
            if ($p->description) {
                $linea .= ": {$p->description}";
            }

            return $linea;
        })->implode("\n");
    }
}
