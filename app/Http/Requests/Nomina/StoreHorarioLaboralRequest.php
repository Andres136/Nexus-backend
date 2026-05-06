<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StoreHorarioLaboralRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'hora_ingreso'          => 'required|date_format:H:i',
            'hora_salida'           => 'required|date_format:H:i|after:hora_ingreso',
            'hora_salida_brake'     => 'nullable|date_format:H:i',
            'horara_ingreso_brake'  => 'nullable|date_format:H:i',
            'hora_salida_almuerzo'  => 'nullable|date_format:H:i',
            'hora_ingreso_almuerzo' => 'nullable|date_format:H:i',
        ];
    }

    public function messages(): array
    {
        return [
            'hora_ingreso.required'       => 'La hora de ingreso es obligatoria.',
            'hora_ingreso.date_format'    => 'La hora de ingreso debe tener formato HH:MM.',
            'hora_salida.required'        => 'La hora de salida es obligatoria.',
            'hora_salida.date_format'     => 'La hora de salida debe tener formato HH:MM.',
            'hora_salida.after'           => 'La hora de salida debe ser después del ingreso.',
        ];
    }
}
