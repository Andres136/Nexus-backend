<?php

namespace App\Http\Requests\Productividad;

use Illuminate\Foundation\Http\FormRequest;

class CompletarActividadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resultado' => 'required|string|max:2000',
            'observacion' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'resultado.required' => 'Debes indicar el resultado obtenido para completar la actividad.',
            'resultado.max' => 'El resultado no puede superar 2000 caracteres.',
            'observacion.max' => 'La observación no puede superar 2000 caracteres.',
        ];
    }
}
