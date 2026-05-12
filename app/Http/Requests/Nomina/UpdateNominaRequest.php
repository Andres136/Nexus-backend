<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNominaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'            => 'sometimes|integer|exists:users,id',
            'jornada_laboral_id' => 'sometimes|integer|exists:jornada_laborals,id',
            'periodo_inicio'     => 'sometimes|nullable|date',
            'periodo_fin'        => 'sometimes|nullable|date|after_or_equal:periodo_inicio',

            'horas_normales'           => 'sometimes|numeric|min:0',
            'horas_extras_nocturnas'   => 'sometimes|numeric|min:0',
            'horas_extras_diurnas'     => 'sometimes|numeric|min:0',
            'horas_festivas'           => 'sometimes|numeric|min:0',
            'horas_nocturnas_festivas' => 'sometimes|numeric|min:0',

            'descuento_id'              => 'sometimes|nullable|integer|exists:descuentos,id',
            'transacional_registros_id' => 'sometimes|nullable|integer|exists:transacional_registros,id',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.exists'                   => 'El empleado no existe.',
            'jornada_laboral_id.exists'        => 'La jornada laboral no existe.',
            'descuento_id.exists'              => 'El descuento no existe.',
            'transacional_registros_id.exists' => 'El registro transaccional no existe.',
        ];
    }
}
