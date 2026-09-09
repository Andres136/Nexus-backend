<?php

namespace App\Http\Requests\RegistroDiario;

use Illuminate\Foundation\Http\FormRequest;

class AnalisisReincidenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'departamento_id' => 'nullable|integer|exists:departamentos,id',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ];
    }

    public function messages(): array
    {
        return [
            'departamento_id.exists' => 'El departamento seleccionado no existe.',
            'fecha_fin.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
        ];
    }
}
