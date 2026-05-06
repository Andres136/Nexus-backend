<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTipoRegistroRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('tipo_registro');

        return [
            'name'   => 'sometimes|string|max:45|unique:tipo_registros,name,' . $id,
            'activo' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.max'    => 'El nombre no puede superar 45 caracteres.',
            'name.unique' => 'Este tipo de registro ya existe.',
        ];
    }
}
