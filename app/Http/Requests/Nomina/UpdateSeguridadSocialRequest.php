<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Nomina\SeguridadSocial;

class UpdateSeguridadSocialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $uuid = $this->route('seguridad_social');

        $id = SeguridadSocial::where('uuid', $uuid)->value('id');

        return [
            'nombre' => 'required|string|max:45',

            'nit' => [
                'required',
                'string',
                'max:45',
                Rule::unique('seguridad_socials', 'nit')->ignore($id),
            ],

            'direccion'    => 'required|string|max:45',
            'fecha_inicio' => 'required|string|max:45',
            'fecha_fin'    => 'nullable|string|max:45',
            'status'       => 'nullable|string|max:45',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'       => 'El nombre es obligatorio.',
            'nit.required'          => 'El NIT es obligatorio.',
            'nit.unique'            => 'Este NIT ya existe.',
            'direccion.required'    => 'La dirección es obligatoria.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
        ];
    }
}
