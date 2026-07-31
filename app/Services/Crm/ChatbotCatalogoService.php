<?php

namespace App\Services\Crm;

use App\Models\Crm\categoria;
use App\Models\Crm\product;

class ChatbotCatalogoService
{
    private const LIMITE = 8;

    /**
     * Texto plano para devolver como resultado de la function call al modelo.
     * Solo expone nombre, descripción y categoría: nunca precio, stock ni
     * códigos internos (SIIGO, inventario), que son datos internos por sede.
     */
    public function consultarProductos(?string $categoriaNombre, ?string $busqueda): string
    {
        $query = product::query()->with('categoria:id,nombre');

        if ($categoriaNombre) {
            $query->whereHas('categoria', fn ($q) => $q->where('nombre', 'LIKE', "%{$categoriaNombre}%"));
        }

        if ($busqueda) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('name', 'LIKE', "%{$busqueda}%")
                    ->orWhere('description', 'LIKE', "%{$busqueda}%");
            });
        }

        $productos = $query->limit(self::LIMITE)->get();

        if ($productos->isEmpty()) {
            return 'No se encontraron productos que coincidan con esa búsqueda. Sugiere hablar con un asesor si necesita algo más específico.';
        }

        return $productos->map(function (product $p) {
            $linea = "- {$p->name}";
            if ($p->categoria) {
                $linea .= " (categoría: {$p->categoria->nombre})";
            }
            if ($p->description) {
                $linea .= ": {$p->description}";
            }

            return $linea;
        })->implode("\n");
    }

    public function listarCategorias(): string
    {
        $categorias = categoria::query()->pluck('nombre');

        if ($categorias->isEmpty()) {
            return 'No hay categorías de productos registradas.';
        }

        return $categorias->implode(', ');
    }
}
