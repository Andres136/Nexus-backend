<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class LiquidarNominaMasivaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periodo_inicio'     => 'required|date',
            'periodo_fin'        => 'required|date|after_or_equal:periodo_inicio',
            'jornada_laboral_id' => 'required|integer|exists:jornada_laborals,id',
            'empresa_id'         => 'nullable|integer|exists:empresas,id',
        ];
    }

    public function messages(): array
    {
        return [
            'periodo_inicio.required'     => 'La fecha de inicio del periodo es obligatoria.',
            'periodo_inicio.date'         => 'La fecha de inicio debe ser una fecha valida.',
            'periodo_fin.required'        => 'La fecha de fin del periodo es obligatoria.',
            'periodo_fin.date'            => 'La fecha de fin debe ser una fecha valida.',
            'periodo_fin.after_or_equal'  => 'La fecha de fin debe ser igual o posterior al inicio.',
            'jornada_laboral_id.required' => 'La jornada laboral es obligatoria.',
            'jornada_laboral_id.exists'   => 'La jornada laboral no existe.',
            'empresa_id.exists'           => 'La empresa seleccionada no existe.',
        ];
    }
}
