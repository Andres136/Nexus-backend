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
            'user_id'                     => 'sometimes|integer|exists:users,id',
            'horas_normales_trabajada_id' => 'sometimes|integer|min:0',
            'horas_extras_nocturnas_id'   => 'sometimes|integer|min:0',
            'horas_extras_diurna_id'      => 'sometimes|integer|min:0',
            'horas_festivas_id'           => 'sometimes|integer|min:0',
            'horas_nocturnas_festivas_id' => 'sometimes|integer|min:0',
            'descuento_id'                => 'sometimes|nullable|integer|exists:descuentos,id',
            'transacional_registros_id'   => 'sometimes|nullable|integer|exists:transacional_registros,id',
            'jornada_laboral_id'          => 'sometimes|integer|exists:jornada_laborals,id',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.exists'                   => 'El empleado no existe',
            'descuento_id.exists'              => 'El descuento no existe',
            'transacional_registros_id.exists' => 'El registro transaccional no existe',
            'jornada_laboral_id.exists'        => 'La jornada laboral no existe',
        ];
    }
}
