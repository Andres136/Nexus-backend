<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class CalificarRespuestaCuestionarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'calificacion' => 'required|integer|min:1|max:5',
        ];
    }

    public function messages(): array
    {
        return [
            'calificacion.required' => 'Indica una calificación.',
            'calificacion.min' => 'La calificación debe estar entre 1 y 5.',
            'calificacion.max' => 'La calificación debe estar entre 1 y 5.',
        ];
    }
}
