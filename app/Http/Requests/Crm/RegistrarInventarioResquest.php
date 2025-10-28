<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarInventarioResquest extends FormRequest
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
            'producto_id' => 'required|exists:products,id',
            'bodega_id' => 'required|exists:bodegas,id',
            'empresa_id' => 'required|exists:empresas,id',
            'fecha_vencimiento' => 'nullable|date|after_or_equal:today',
            'stock' => 'required|integer|min:1',
            'precio' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'max_stock' => 'nullable|integer|min:0|gte:min_stock',
        ];
    }


    public function messages(): array
    {
        return [
            'producto_id.required' => 'El campo producto es obligatorio.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
            'bodega_id.required' => 'El campo bodega es obligatorio.',
            'bodega_id.exists' => 'La bodega seleccionada no existe.',
            'empresa_id.required' => 'El campo empresa es obligatorio.',
            'empresa_id.exists' => 'La empresa seleccionada no existe.',
            'fecha_vencimiento.date' => 'El campo fecha de vencimiento debe ser una fecha válida.',
            'fecha_vencimiento.after_or_equal' => 'La fecha de vencimiento no puede ser anterior a hoy.',
            'min_stock.integer' => 'El campo stock mínimo debe ser un número entero.',
            'min_stock.min' => 'El campo stock mínimo no puede ser negativo.',
            'stock.integer' => 'El campo stock debe ser un número entero.',
            'stock.min' => 'El campo stock debe ser al menos 1.',
            'bodega_id.required' => 'El campo bodega es obligatorio.',
            'bodega_id.exists' => 'La bodega seleccionada no existe.',
            'empresa_id.required' => 'El campo empresa es obligatorio.',
            'empresa_id.exists' => 'La empresa seleccionada no existe.',
            'fecha_vencimiento.date' => 'El campo fecha de vencimiento debe ser una fecha válida.',
            'fecha_vencimiento.after_or_equal' => 'La fecha de vencimiento no puede ser anterior a hoy.',
            'min_stock.integer' => 'El campo stock mínimo debe ser un número entero.',
            'min_stock.min' => 'El campo stock mínimo no puede ser negativo.',
            'max_stock.integer' => 'El campo stock máximo debe ser un número entero.',
            'max_stock.min' => 'El campo stock máximo no puede ser negativo.',
            'max_stock.gte' => 'El campo stock máximo debe ser mayor o igual al stock mínimo.',
        ];
    }
}
