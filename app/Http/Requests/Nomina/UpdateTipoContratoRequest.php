<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTipoContratoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('tipo_contrato');

        return [
            'nombre'      => 'sometimes|string|max:100',
            'codigo'      => 'sometimes|string|max:20|unique:tipo_contratos,codigo,' . $id,
            'descripcion' => 'nullable|string',
            'activo'      => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.max'    => 'El nombre no puede superar 100 caracteres.',
            'codigo.unique' => 'Este código ya existe.',
            'codigo.max'    => 'El código no puede superar 20 caracteres.',
        ];
    }
}
