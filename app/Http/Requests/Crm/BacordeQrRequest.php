<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class BacordeQrRequest extends FormRequest
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
        'empresa_id' => 'required|exists:empresas,id',
        'productos' => 'required|array|min:1',
        'productos.*.producto_id' => 'required|exists:products,id',
        ];
    }

    public function messages(): array
    {
        return [
            'empresa_id.required' => 'El campo empresa es obligatorio.',
            'empresa_id.exists' => 'La empresa seleccionada no es válida.',
            'productos.required' => 'El campo productos es obligatorio.',
            'productos.array' => 'El campo productos debe ser un array.',
            'productos.min' => 'El campo productos debe contener al menos un producto.',
            'productos.*.producto_id.required' => 'El campo producto es obligatorio.',
            'productos.*.producto_id.exists' => 'El producto seleccionado no es válido.',
        ];
    }
}
