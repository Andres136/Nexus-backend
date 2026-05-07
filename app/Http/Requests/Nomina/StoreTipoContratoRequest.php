<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StoreTipoContratoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre'      => 'required|unique:tipo_contratos,nombre|max:100',
            'descripcion' => 'nullable|string',
            'activo'      => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
     
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique'   => 'Este nombre ya existe.',
            'nombre.max'      => 'El nombre no puede superar 100 caracteres.',
        ];
    }
}