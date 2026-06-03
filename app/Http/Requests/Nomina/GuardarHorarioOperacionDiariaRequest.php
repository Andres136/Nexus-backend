<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class GuardarHorarioOperacionDiariaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha' => 'required|date',
            'kiosko_device_id' => 'nullable|integer|exists:kiosko_devices,id',
            'jornada_laboral_id' => 'nullable|integer|exists:jornada_laborals,id',
            'hora_entrada' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'hora_entrada_limite' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'hora_salida_pausa' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'hora_ingreso_pausa' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'hora_salida_almuerzo' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'hora_ingreso_almuerzo' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'hora_salida' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'duracion_pausa_minutos' => 'nullable|integer|min:1|max:180',
            'duracion_almuerzo_minutos' => 'nullable|integer|min:1|max:240',
            'motivo' => 'nullable|string|max:180',
            'status' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.required' => 'La fecha de la instrucción es obligatoria.',
            'kiosko_device_id.exists' => 'El kiosko seleccionado no existe.',
            'jornada_laboral_id.exists' => 'La jornada laboral no existe.',
            'duracion_pausa_minutos.max' => 'La pausa no puede superar 180 minutos.',
            'duracion_almuerzo_minutos.max' => 'El almuerzo no puede superar 240 minutos.',
        ];
    }
}
