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
            'hora_entrada' => ['sometimes', 'nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'hora_salida_almuerzo' => ['sometimes', 'nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'hora_ingreso_almuerzo' => ['sometimes', 'nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'hora_salida_pausa' => ['sometimes', 'nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'hora_ingreso_pausa' => ['sometimes', 'nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'hora_salida' => ['sometimes', 'nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'duracion_pausa_minutos' => 'sometimes|nullable|integer|min:1|max:180',
            'duracion_almuerzo_minutos' => 'sometimes|nullable|integer|min:1|max:240',
            'comando_voz_activo' => 'sometimes|boolean',
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
            'duracion_pausa_minutos.max' => 'La pausa no puede superar 180 minutos.',
            'duracion_almuerzo_minutos.max' => 'El almuerzo no puede superar 240 minutos.',
        ];
    }
}
