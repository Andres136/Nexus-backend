<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class StockMasivoRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1'],
        'items.*.producto_id' => ['required', 'integer', 'exists:products,id'],
        'items.*.cantidad' => ['required', 'numeric', 'min:1'],
        'items.*.orden_trabajo_id' => ['nullable', 'integer'],
        'items.*.orden_compra_id' => ['nullable', 'integer'],
        'items.*.bodegas' => ['required', 'array', 'min:1'],
        'items.*.bodegas.*.bodega_id' => ['required', 'integer', 'exists:bodegas,id'],
        'items.*.bodegas.*.cantidad' => ['required', 'numeric', 'min:0'],
        'items.*.producto_equivalentes' => ['nullable', 'array'],
        'items.*.producto_equivalentes.*.id' => ['required_with:items.*.producto_equivalentes', 'integer', 'exists:products,id'],
        'items.*.producto_equivalentes.*.razon' => ['nullable', 'string'],
        'items.*.producto_equivalentes.*.bodegas' => ['required_with:items.*.producto_equivalentes', 'array'],
        'items.*.producto_equivalentes.*.bodegas.*.bodega_id' => ['required_with:items.*.producto_equivalentes.*.bodegas', 'integer', 'exists:bodegas,id'],
        'items.*.producto_equivalentes.*.bodegas.*.cantidad' => ['required_with:items.*.producto_equivalentes.*.bodegas', 'numeric', 'min:0'],
        ];
    }


    public function messages(): array
    {
        return [
            'items.required' => 'Se requiere al menos un producto para descontar stock.',
            'items.array' => 'El campo de productos debe ser un arreglo.',
            'items.min' => 'Se requiere al menos un producto para descontar stock.',
            'items.*.producto_id.required' => 'El ID del producto es obligatorio.',
            'items.*.producto_id.integer' => 'El ID del producto debe ser un número entero.',
            'items.*.producto_id.exists' => 'El producto seleccionado no existe.',
            'items.*.cantidad.required' => 'La cantidad a descontar es obligatoria.',
            'items.*.cantidad.numeric' => 'La cantidad a descontar debe ser un número.',
            'items.*.cantidad.min' => 'La cantidad a descontar debe ser al menos 1.',
            'items.*.bodegas.required' => 'Se requiere al menos una bodega para cada producto.',
            'items.*.bodegas.array' => 'El campo de bodegas debe ser un arreglo.',
            'items.*.bodegas.min' => 'Se requiere al menos una bodega para cada producto.',
            'items.*.bodegas.*.bodega_id.required' => 'El ID de la bodega es obligatorio.',
            'items.*.bodegas.*.bodega_id.integer' => 'El ID de la bodega debe ser un número entero.',
            'items.*.bodegas.*.bodega_id.exists' => 'La bodega seleccionada no existe.',
            'items.*.bodegas.*.cantidad.required' => 'La cantidad en la bodega es obligatoria.',
            'items.*.bodegas.*.cantidad.numeric' => 'La cantidad en la bodega debe ser un número.',
            'items.*.bodegas.*.cantidad.min' => 'La cantidad en la bodega no puede ser negativa.',
        ];
    }
}
