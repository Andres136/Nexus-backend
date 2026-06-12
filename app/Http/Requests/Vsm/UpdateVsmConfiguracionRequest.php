<?php

namespace App\Http\Requests\Vsm;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVsmConfiguracionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'meta_unidades_hora' => 'required|numeric|min:1|max:99999',
            'horas_semanales'    => 'required|numeric|min:1|max:168',
            'descripcion'        => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'meta_unidades_hora.required' => 'La meta de unidades por hora es obligatoria.',
            'meta_unidades_hora.numeric'  => 'La meta debe ser un número.',
            'meta_unidades_hora.min'      => 'La meta debe ser mayor a 0.',
            'meta_unidades_hora.max'      => 'La meta no puede superar 99999 unidades/hora.',
            'horas_semanales.required'    => 'Las horas semanales son obligatorias.',
            'horas_semanales.numeric'     => 'Las horas semanales deben ser un número.',
            'horas_semanales.min'         => 'Las horas semanales deben ser mayor a 0.',
            'horas_semanales.max'         => 'Las horas semanales no pueden superar 168.',
            'descripcion.max'             => 'La descripción no puede superar 255 caracteres.',
        ];
    }
}
