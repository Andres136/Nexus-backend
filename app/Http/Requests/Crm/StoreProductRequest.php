<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'siigo_id' => 'nullable|string|max:100|unique:products,siigo_id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'code' => 'required|string|max:100|unique:products,code',
            'categoria_id' => 'required|exists:categorias,id',
            'inventarios' => 'nullable|array',
            'inventarios.*.empresa_id' => 'required_with:inventarios|exists:empresas,id',
            'inventarios.*.sede_id' => 'required_with:inventarios|exists:sedes,id',
            'inventarios.*.bodega_id' => 'required_with:inventarios|exists:bodegas,id',
            'stock' => 'nullable|numeric|min:0',
            'inventarios.*.stock' => 'nullable|numeric|min:0',
            'inventarios.*.precio' => 'nullable|numeric|min:0',
            'inventarios.*.min_stock' => 'nullable|integer|min:0',
            'inventarios.*.max_stock' => 'nullable|integer|min:0',
            'inventarios.*.fecha_vencimiento' => 'nullable|date',
        ];
    }

    public function messages()
    {
        return [
            'siigo_id.string' => 'El campo siigo_id debe ser una cadena de texto.',
            'name.required' => 'El campo nombre es obligatorio.',
            'name.string' => 'El campo nombre debe ser una cadena de texto.',
            'name.max' => 'El campo nombre no debe exceder los 255 caracteres.',
            'description.string' => 'El campo descripción debe ser una cadena de texto.',
            'code.required' => 'El campo código es obligatorio.',
            'code.string' => 'El campo código debe ser una cadena de texto.',
            'code.max' => 'El campo código no debe exceder los 100 caracteres.',
            'code.unique' => 'El código ya está en uso. Por favor, elige otro código.',
            'categoria_id.required' => 'El campo categoría es obligatorio.',
            'categoria_id.exists' => 'La categoría seleccionada no es válida.',


            'inventarios.array' => 'El campo inventarios debe ser un arreglo.',
            'inventarios.*.empresa_id.required_with' => 'El campo empresa es obligatorio cuando se proporcionan inventarios.',
            'inventarios.*.empresa_id.exists' => 'La empresa seleccionada no es válida.',
            'inventarios.*.sede_id.required_with' => 'El campo sede es obligatorio cuando se proporcionan inventarios.',
            'inventarios.*.sede_id.exists' => 'La sede seleccionada no es válida.',
            'inventarios.*.bodega_id.required_with' => 'El campo bodega es obligatorio cuando se proporcionan inventarios.',
            'inventarios.*.bodega_id.exists' => 'La bodega seleccionada no es válida.',
            'inventarios.*.stock.integer' => 'El campo stock debe ser un número entero.',
            'inventarios.*.stock.min' => 'El campo stock no puede ser negativo.',
            'inventarios.*.precio.numeric' => 'El campo precio debe ser un número.',
            'inventarios.*.precio.min' => 'El campo precio no puede ser negativo.',
            'inventarios.*.min_stock.integer' => 'El campo stock mínimo debe ser un número entero.',
            'inventarios.*.min_stock.min' => 'El campo stock mínimo no puede ser negativo.',
            'inventarios.*.max_stock.integer' => 'El campo stock máximo debe ser un número entero.',
            'inventarios.*.max_stock.min' => 'El campo stock máximo no puede ser negativo.',
            'inventarios.*.fecha_vencimiento.date' => 'El campo fecha de vencimiento debe ser una fecha válida.',
        ];
    }
}
