<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StoreTipoContratoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre'      => 'required|string|max:100',
            'codigo'      => 'required|string|max:20|unique:tipo_contratos,codigo',
            'descripcion' => 'nullable|string',
            'activo'      => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max'      => 'El nombre no puede superar 100 caracteres.',
            'codigo.required' => 'El código es obligatorio.',
            'codigo.unique'   => 'Este código ya existe.',
            'codigo.max'      => 'El código no puede superar 20 caracteres.',
        ];
    }
}