<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Producto original
            'producto_id' => ['required', 'integer', 'exists:products,id'],
            'cantidad'    => ['required', 'numeric', 'min:1'],

            // Bodegas del producto original
            // Bodegas del producto original
            // Bodegas del producto original
            'bodegas' => 'nullable|array',
            'bodegas' => 'required_without:producto_equivalentes',
            'bodegas.*.bodega_id' => 'required_with:bodegas|integer|exists:bodegas,id',
            'bodegas.*.cantidad'  => ['required_with:bodegas', 'numeric', 'min:0.01'],


            // Equivalentes
            'producto_equivalentes' => ['nullable', 'array'],
     'producto_equivalentes.*.id' => [
    'required_with:producto_equivalentes',
    'integer',
    'min:1',
    'exists:products,id',
    'different:producto_id',
    'distinct'
],

            'producto_equivalentes.*.razon' => ['nullable', 'string', 'max:255'],

            // Bodegas de equivalentes
            'producto_equivalentes.*.bodegas' => ['required_with:producto_equivalentes', 'array', 'min:1'],
            'producto_equivalentes.*.bodegas.*.bodega_id' => ['required', 'integer', 'exists:bodegas,id'],
            'producto_equivalentes.*.bodegas.*.cantidad'  => ['required', 'numeric', 'min:1'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function (Validator $validator) {
            $productoId = (int) $this->input('producto_id');
            $cantidadTotal = (int) $this->input('cantidad');

            // ✅ Stock de bodegas originales
            $bodegasOriginales = $this->input('bodegas', []);
            $cantidadOriginalAsignada = 0;

            foreach ($bodegasOriginales as $idx => $bodega) {
                $bodegaId = (int) ($bodega['bodega_id'] ?? 0);
                $cantidadBodega = (int) ($bodega['cantidad'] ?? 0);

                $stockDisponible = \App\Models\Crm\Inventario::where('producto_id', $productoId)
                    ->where('bodega_id', $bodegaId)
                    ->sum('stock');

                if ($stockDisponible < $cantidadBodega) {
                    $validator->errors()->add(
                        "bodegas.{$idx}.cantidad",
                        "Stock insuficiente en bodega. Disponible: {$stockDisponible}, Requerido: {$cantidadBodega}"
                    );
                }

                $cantidadOriginalAsignada += $cantidadBodega;
            }

            // ✅ Déficit a cubrir con equivalentes
            $deficit = max(0, $cantidadTotal - $cantidadOriginalAsignada);

            if ($deficit === 0) {
                return; // No necesitamos equivalentes
            }

            $equivalentes = $this->input('producto_equivalentes', []);
            if (empty($equivalentes)) {
                $validator->errors()->add(
                    'producto_equivalentes',
                    "Falta cubrir {$deficit} Kg . Debe agregar productos equivalentes."
                );
                return;
            }

            // ✅ Validar equivalentes
            $cantidadEquivalentesTotal = 0;

            foreach ($equivalentes as $eqIdx => $equivalente) {
                $eqId = (int) ($equivalente['id'] ?? 0);
                $bodegasEq = $equivalente['bodegas'] ?? [];

                foreach ($bodegasEq as $bodegaIdx => $bodega) {
                    $bodegaId = (int) ($bodega['bodega_id'] ?? 0);
                    $cantidadBodega = (int) ($bodega['cantidad'] ?? 0);

                    $stockDisponible = \App\Models\Crm\Inventario::where('producto_id', $eqId)
                        ->where('bodega_id', $bodegaId)
                        ->sum('stock');

                    if ($stockDisponible < $cantidadBodega) {
                        $validator->errors()->add(
                            "producto_equivalentes.{$eqIdx}.bodegas.{$bodegaIdx}.cantidad",
                            "Stock insuficiente en bodega para equivalente. Disponible: {$stockDisponible}, Requerido: {$cantidadBodega}"
                        );
                    }

                    $cantidadEquivalentesTotal += $cantidadBodega;
                }
            }

            if ($cantidadEquivalentesTotal < $deficit) {
                $validator->errors()->add(
                    'producto_equivalentes',
                    "Cantidad insuficiente de equivalentes. Necesita {$deficit}, tiene {$cantidadEquivalentesTotal}."
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            // Producto
            'producto_id.required' => 'El producto es obligatorio.',
            'producto_id.exists'   => 'El producto no existe.',
            'cantidad.required'    => 'La cantidad total es obligatoria.',
            'cantidad.min'         => 'La cantidad debe ser mayor a 0.',
            'cantidad.numeric'     => 'La cantidad debe ser un número.',

            // Bodegas del producto original    
            'bodegas.required_without' => 'Debe especificar al menos una bodega o un producto equivalente.',
            'bodegas.array' => 'El formato de bodegas no es válido.',
            'bodegas.*.bodega_id.required' => 'Debe seleccionar la bodega.',
            'bodegas.*.bodega_id.exists'   => 'La bodega no existe.',
            'bodegas.*.cantidad.required'  => 'Debe indicar la cantidad.',
               'bodegas.*.cantidad.min'       => 'La cantidad debe ser mayor a 0.', // ✅ esta es la clave que faltaba


            // Equivalentes

            'producto_equivalentes.array' => 'El formato de equivalentes no es válido.',
            'producto_equivalentes.*.id.required_with' => 'Debe seleccionar el producto equivalente.',
            'producto_equivalentes.*.id.different' => 'El equivalente debe ser diferente al producto original.',
            'producto_equivalentes.*.id.exists' => 'El producto equivalente no existe.',

            // Bodegas de equivalentes
            'producto_equivalentes.*.bodegas.required_with' => 'Debe especificar bodegas para el equivalente.',
            'producto_equivalentes.*.bodegas.array' => 'El formato de bodegas del equivalente no es válido.',
            
            'producto_equivalentes.*.bodegas.*.bodega_id.required' => 'Debe seleccionar la bodega para el equivalente.',
            'producto_equivalentes.*.bodegas.*.bodega_id.exists'   => 'La bodega del equivalente no existe.',
            
            'producto_equivalentes.*.bodegas.*.cantidad.required'  => 'Debe indicar la cantidad.',
            'producto_equivalentes.*.bodegas.*.cantidad.min'       => 'La cantidad debe ser mayor a 0.',
            
        ];
    }
}
