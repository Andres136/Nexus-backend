<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarHorarioUsuarioSemanalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                Rule::requiredIf(fn () => ! $this->boolean('aplicar_todos')),
            ],
            'aplicar_todos' => 'sometimes|boolean',
            'horarios' => 'required|array|min:1|max:7',
            'horarios.*.dia_semana' => 'required|integer|min:1|max:7',
            'horarios.*.jornada_laboral_id' => 'nullable|integer|exists:jornada_laborals,id',
            'horarios.*.hora_entrada' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'horarios.*.hora_entrada_limite' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'horarios.*.hora_salida_pausa' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'horarios.*.hora_ingreso_pausa' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'horarios.*.hora_salida_almuerzo' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'horarios.*.hora_ingreso_almuerzo' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'horarios.*.hora_salida' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'horarios.*.duracion_pausa_minutos' => 'nullable|integer|min:1|max:180',
            'horarios.*.duracion_almuerzo_minutos' => 'nullable|integer|min:1|max:240',
            'horarios.*.status' => 'sometimes|boolean',
        ];
    }
}
