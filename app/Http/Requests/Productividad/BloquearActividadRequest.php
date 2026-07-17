<?php

namespace App\Http\Requests\Productividad;

use Illuminate\Foundation\Http\FormRequest;

class BloquearActividadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo_bloqueo' => 'required|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'motivo_bloqueo.required' => 'Debes indicar el motivo del bloqueo.',
            'motivo_bloqueo.max' => 'El motivo no puede superar 2000 caracteres.',
        ];
    }
}
