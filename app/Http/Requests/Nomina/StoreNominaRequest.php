<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StoreNominaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'            => 'required|integer|exists:users,id',
            'jornada_laboral_id' => 'required|integer|exists:jornada_laborals,id',
            'periodo_inicio'     => 'nullable|date',
            'periodo_fin'        => 'nullable|date|after_or_equal:periodo_inicio',

            'horas_normales'           => 'required|numeric|min:0',
            'horas_extras_nocturnas'   => 'required|numeric|min:0',
            'horas_extras_diurnas'     => 'required|numeric|min:0',
            'horas_festivas'           => 'required|numeric|min:0',
            'horas_nocturnas_festivas' => 'required|numeric|min:0',

            'descuento_id'              => 'nullable|integer|exists:descuentos,id',
            'transacional_registros_id' => 'nullable|integer|exists:transacional_registros,id',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required'                => 'El empleado es obligatorio.',
            'user_id.exists'                  => 'El empleado no existe.',
            'jornada_laboral_id.required'     => 'La jornada laboral es obligatoria.',
            'jornada_laboral_id.exists'       => 'La jornada laboral no existe.',
            'horas_normales.required'         => 'Las horas normales son obligatorias.',
            'horas_normales.min'              => 'Las horas no pueden ser negativas.',
            'horas_extras_nocturnas.required' => 'Las horas extras nocturnas son obligatorias.',
            'horas_extras_diurnas.required'   => 'Las horas extras diurnas son obligatorias.',
            'horas_festivas.required'         => 'Las horas festivas son obligatorias.',
            'horas_nocturnas_festivas.required' => 'Las horas nocturnas festivas son obligatorias.',
            'descuento_id.exists'             => 'El descuento no existe.',
            'transacional_registros_id.exists' => 'El registro transaccional no existe.',
        ];
    }
}
