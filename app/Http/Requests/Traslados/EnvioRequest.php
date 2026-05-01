<?php

namespace App\Http\Requests\Traslados;

use App\Models\Crm\Inventario;
use Illuminate\Foundation\Http\FormRequest;

class EnvioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            //  General
        
            'sede_destino_id' => 'required|exists:sedes,id|different:sede_origen_id',
            'notas' => 'nullable|string|max:500',
            'empresa_id' => 'required|exists:empresas,id',

     

            // Detalles
            'detalles' => 'required|array|min:1',
            'detalles.*.orden_compra_id' => 'required|exists:orden_compra_proveedores,id',
            'detalles.*.product_id' => 'required|exists:products,id',
            'detalles.*.descripcion' => 'required|string|max:255',
            'detalles.*.novedades' => 'nullable|string|max:255',

            //  Estructura antigua (compatibilidad)
            'detalles.*.bodega_origen_id' => 'sometimes|integer|exists:bodegas,id',
            'detalles.*.cantidad' => 'sometimes|numeric|min:0.01',

            //  Estructura nueva (varias bodegas)
            'detalles.*.bodegas' => 'required|array|min:1',
            'detalles.*.bodegas.*.bodega_id' => 'required_with:detalles.*.bodegas|exists:bodegas,id',
            'detalles.*.bodegas.*.cantidad' => 'required_with:detalles.*.bodegas|numeric|min:0.01',
        ];
    }
public function withValidator($validator)
{
    $validator->after(function ($validator) {
        foreach ($this->detalles as $detalleIndex => $detalle) {

            if (!empty($detalle['bodegas'])) {
                foreach ($detalle['bodegas'] as $bodegaIndex => $bodega) {

                    $stockDisponible = Inventario::where(
                        'producto_id',
                        $detalle['product_id']
                    )
                    ->where('bodega_id', $bodega['bodega_id'])
                    ->sum('stock');

                    if ($bodega['cantidad'] > $stockDisponible) {
                        $validator->errors()->add(
                            "detalles.$detalleIndex.bodegas.$bodegaIndex.cantidad",
                            "Stock insuficiente en bodega {$bodega['bodega_id']} ({$stockDisponible} disponibles)."
                        );
                    }
                }
            }
        }
    });
}
    public function messages(): array
    {
        return [
            // General

            'empresa_id.required' => 'La empresa es obligatoria.',
            'empresa_id.exists' => 'La empresa seleccionada no es válida.',
            'sede_destino_id.required' => 'La sede de destino es obligatoria.',
            'sede_destino_id.exists' => 'La sede de destino seleccionada no es válida.',
            'sede_destino_id.different' => 'La sede de destino debe ser diferente a la sede de origen.',
            'notas.max' => 'Las notas no pueden exceder los 500 caracteres.',

          

            //  Detalles
            'detalles.required' => 'Debe agregar al menos un detalle de envío.',
            'detalles.array' => 'El formato de los detalles de envío no es válido.',
            'detalles.min' => 'Debe agregar al menos un detalle de envío.',
            'detalles.*.orden_compra_id.required' => 'El ID de la orden de compra es obligatorio en cada detalle.',
            'detalles.*.orden_compra_id.exists' => 'El ID de la orden de compra en uno de los detalles no es válido.',
            'detalles.*.product_id.required' => 'El ID del producto es obligatorio en cada detalle.',
            'detalles.*.product_id.exists' => 'El ID del producto en uno de los detalles no es válido.',
            'detalles.*.descripcion.required' => 'La descripción es obligatoria en cada detalle.',
            'detalles.*.descripcion.max' => 'La descripción no puede exceder los 255 caracteres.',
            'detalles.*.novedades.max' => 'Las observaciones no pueden exceder los 255 caracteres.',

            // Compatibilidad con estructura antigua
            'detalles.*.bodega_origen_id.required' => 'El ID de la bodega de origen es obligatorio en cada detalle.',
            'detalles.*.bodega_origen_id.exists' => 'El ID de la bodega de origen en uno de los detalles no es válido.',
            'detalles.*.cantidad.required' => 'La cantidad es obligatoria en cada detalle.',
            'detalles.*.cantidad.numeric' => 'La cantidad debe ser un número válido en cada detalle.',
            'detalles.*.cantidad.min' => 'La cantidad debe ser al menos 0.01 en cada detalle.',

            //  Validación de estructura nueva
            'detalles.*.bodegas.required' => 'Debe incluir al menos una bodega en cada detalle.',
            'detalles.*.bodegas.array' => 'El formato de las bodegas no es válido.',
            'detalles.*.bodegas.*.bodega_id.required_with' => 'El ID de la bodega es obligatorio dentro del bloque de bodegas.',
            'detalles.*.bodegas.*.bodega_id.exists' => 'El ID de una de las bodegas no es válido.',
            'detalles.*.bodegas.*.cantidad.required_with' => 'La cantidad es obligatoria en cada bodega.',
            'detalles.*.bodegas.*.cantidad.numeric' => 'La cantidad en cada bodega debe ser numérica.',
            'detalles.*.bodegas.*.cantidad.min' => 'La cantidad en cada bodega debe ser mayor a 0.',
        ];
    }
}
