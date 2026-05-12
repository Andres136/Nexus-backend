<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class LiquidarNominaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'            => 'required|integer|exists:users,id',
            'periodo_inicio'     => 'required|date',
            'periodo_fin'        => 'required|date|after_or_equal:periodo_inicio',
            'jornada_laboral_id' => 'required|integer|exists:jornada_laborals,id',
            'descuento_id'       => 'nullable|integer|exists:descuentos,id',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required'             => 'El empleado es obligatorio.',
            'user_id.exists'               => 'El empleado no existe.',
            'periodo_inicio.required'      => 'La fecha de inicio del período es obligatoria.',
            'periodo_inicio.date'          => 'La fecha de inicio debe ser una fecha válida.',
            'periodo_fin.required'         => 'La fecha de fin del período es obligatoria.',
            'periodo_fin.date'             => 'La fecha de fin debe ser una fecha válida.',
            'periodo_fin.after_or_equal'   => 'La fecha de fin debe ser igual o posterior al inicio.',
            'jornada_laboral_id.required'  => 'La jornada laboral es obligatoria.',
            'jornada_laboral_id.exists'    => 'La jornada laboral no existe.',
            'descuento_id.exists'          => 'El descuento no existe.',
        ];
    }
}
