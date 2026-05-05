<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSeguridadSocialRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('seguridad_social');

        return [
            'nombre'       => 'sometimes|string|max:45',
            'nit'          => 'sometimes|string|max:45|unique:seguridad_socials,nit,' . $id,
            'direccion'    => 'sometimes|string|max:45',
            'fecha_inicio' => 'sometimes|string|max:45',
            'fecha_fin'    => 'nullable|string|max:45',
            'status'       => 'nullable|string|max:45',
        ];
    }

    public function messages(): array
    {
        return [
            'nit.unique' => 'Este NIT ya existe.',
        ];
    }
}
