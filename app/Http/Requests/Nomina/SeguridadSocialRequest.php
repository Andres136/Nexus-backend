<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class SeguridadSocialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('seguridad_social');

        return [
            'nombre'      => 'required|string|max:45',
            'nit'         => 'required|string|max:45|unique:seguridad_social,nit,' . $id,
            'direccion'   => 'required|string|max:45',
            'fecha_inicio'=> 'required|string|max:45',
            'fecha_fin'   => 'nullable|string|max:45',
            'status'      => 'nullable|string|max:45',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'       => 'El nombre es obligatorio.',
            'nombre.max'            => 'El nombre no puede superar 45 caracteres.',
            'nit.required'          => 'El NIT es obligatorio.',
            'nit.unique'            => 'Este NIT ya existe.',
            'direccion.required'    => 'La dirección es obligatoria.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
        ];
    }
}
