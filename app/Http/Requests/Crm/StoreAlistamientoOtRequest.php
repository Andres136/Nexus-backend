<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class StoreAlistamientoOtRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
 public function rules(): array
{
    return [
        'items' => 'required|array|min:1',

        'items.*.orden_trabajo_id' => 'required|integer|exists:orden_de_trabajos,id',
        'items.*.orden_compra_detalle_id' => 'required|integer|exists:orden__compra__detalles,id',
        'items.*.producto_id' => 'required|integer|exists:products,id',
        'items.*.bodega_id' => 'required|integer|exists:bodegas,id',
        'items.*.cantidad' => 'required|numeric|min:0.01',
        'items.*.tipo' => 'required|in:original,equivalente',
        'items.*.observacion' => 'nullable|string|max:255',
    ];
}


    public function messages()
    {
        return [
            'items.required' => 'El campo items es obligatorio.',
            'items.array' => 'El campo items debe ser un array.',
            'items.min' => 'Debe haber al menos un item en el array.',

            'items.*.orden_trabajo_id.required' => 'El campo orden_trabajo_id es obligatorio para cada item.',
            'items.*.orden_trabajo_id.integer' => 'El campo orden_trabajo_id debe ser un número entero.',
            'items.*.orden_trabajo_id.exists' => 'El orden_trabajo_id no existe en la base de datos.',

            'items.*.orden_compra_detalle_id.required' => 'El campo orden_compra_detalle_id es obligatorio para cada item.',
            'items.*.orden_compra_detalle_id.integer' => 'El campo orden_compra_detalle_id debe ser un número entero.',
            'items.*.orden_compra_detalle_id.exists' => 'El orden_compra_detalle_id no existe en la base de datos.',

            'items.*.producto_id.required' => 'El campo producto_id es obligatorio para cada item.',
            'items.*.producto_id.integer' => 'El campo producto_id debe ser un número entero.',
            'items.*.producto_id.exists' => 'El producto_id no existe en la base de datos.',

            'items.*.bodega_id.required' => 'El campo bodega_id es obligatorio para cada item.',
            'items.*.bodega_id.integer' => 'El campo bodega_id debe ser un número entero.',
            'items.*.bodega_id.exists' => 'El bodega_id no existe en la base de datos.',

            'items.*.cantidad.required' => 'El campo cantidad es obligatorio para cada item.',
            'items.*.cantidad.numeric' => 'El campo cantidad debe ser un número.',
            'items.*.cantidad.min' => 'La cantidad debe ser al menos 0.01.',

            'items.*.tipo.required' => 'El campo tipo es obligatorio para cada item.',
            'items.*.tipo.in' => 'El campo tipo debe ser "original" o "equivalente".',
            'items.*.observacion.string' => 'El campo observacion debe ser una cadena de texto.',
            'items.*.observacion.max' => 'El campo observacion no debe exceder los 255 caracteres.',
        ];
    }
}
