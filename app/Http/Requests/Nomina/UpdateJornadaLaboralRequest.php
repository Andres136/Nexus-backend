<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJornadaLaboralRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre'          => 'sometimes|string|max:100',
            'horas_semanales' => 'sometimes|integer|min:1|max:48',
            'status'          => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.string'           => 'El nombre debe ser texto.',
            'nombre.max'              => 'El nombre no puede superar 100 caracteres.',
            'horas_semanales.integer' => 'Las horas semanales deben ser un número entero.',
            'horas_semanales.min'     => 'Las horas semanales deben ser mínimo 1.',
            'horas_semanales.max'     => 'Las horas semanales no pueden superar 48.',
        ];
    }
}
