<?php

namespace App\Http\Requests\comunicaciones;

use Illuminate\Foundation\Http\FormRequest;

class VentanaWebRegistroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'correo' => ['required', 'email', 'max:255'],
            'telefono' => ['required', 'string', 'max:30'],
            'mensaje' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'El correo no es válido.',
            'telefono.required' => 'El teléfono es obligatorio.',
        ];
    }
}
