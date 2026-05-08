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
            // ¿A qué empleado pertenece?
            'user_id'                     => 'required|integer|exists:users,id',

            // Horas calculadas del período
            'horas_normales_trabajada_id' => 'required|integer|min:0',
            'horas_extras_nocturnas_id'   => 'required|integer|min:0',
            'horas_extras_diurna_id'      => 'required|integer|min:0',
            'horas_festivas_id'           => 'required|integer|min:0',
            'horas_nocturnas_festivas_id' => 'required|integer|min:0',

            // FKs opcionales
            'descuento_id'                => 'nullable|integer|exists:descuentos,id',
            'transacional_registros_id'   => 'nullable|integer|exists:transacional_registros,id',
            'jornada_laboral_id'          => 'required|integer|exists:jornada_laborals,id',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required'                     => 'El empleado es obligatorio',
            'user_id.exists'                       => 'El empleado no existe',
            'horas_normales_trabajada_id.required' => 'Las horas normales son obligatorias',
            'horas_normales_trabajada_id.min'      => 'Las horas no pueden ser negativas',
            'horas_extras_nocturnas_id.required'   => 'Las horas extras nocturnas son obligatorias',
            'horas_extras_diurna_id.required'      => 'Las horas extras diurnas son obligatorias',
            'horas_festivas_id.required'           => 'Las horas festivas son obligatorias',
            'horas_nocturnas_festivas_id.required' => 'Las horas nocturnas festivas son obligatorias',
            'descuento_id.exists'                  => 'El descuento no existe',
            'transacional_registros_id.exists'     => 'El registro transaccional no existe',
            'jornada_laboral_id.required'          => 'La jornada laboral es obligatoria',
            'jornada_laboral_id.exists'            => 'La jornada laboral no existe',
        ];
    }
}
