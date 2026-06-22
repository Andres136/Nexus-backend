<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class RevertirNominaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => 'required|string|min:10|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'El motivo de la reversión es obligatorio.',
            'motivo.min' => 'El motivo debe tener al menos 10 caracteres.',
        ];
    }
}
