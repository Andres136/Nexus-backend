<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHorarioLaboralRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'hora_ingreso'          => 'sometimes|date_format:H:i',
            'hora_salida'           => 'sometimes|date_format:H:i',
            'hora_salida_brake'     => 'nullable|date_format:H:i',
            'hora_ingreso_brake'  => 'nullable|date_format:H:i',
            'hora_salida_almuerzo'  => 'nullable|date_format:H:i',
            'hora_ingreso_almuerzo' => 'nullable|date_format:H:i',
        ];
    }

    public function messages(): array
    {
        return [
            'hora_ingreso.date_format' => 'La hora de ingreso debe tener formato HH:MM.',
            'hora_salida.date_format'  => 'La hora de salida debe tener formato HH:MM.',
        ];
    }
}
