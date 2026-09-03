<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class GuardarBorradorCuestionarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'respuestas' => 'required|array|min:1',
            'respuestas.*.pregunta_id' => 'required|integer',
            'respuestas.*.valor' => 'nullable|string|max:5000',
        ];
    }
}
