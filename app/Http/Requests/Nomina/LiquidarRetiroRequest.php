<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class LiquidarRetiroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'jornada_laboral_id' => 'required|integer|exists:jornada_laborals,id',
            'fecha_retiro' => 'required|date|before_or_equal:today',
            'motivo_retiro' => 'required|in:renuncia,terminacion_sin_justa_causa,terminacion_con_justa_causa,mutuo_acuerdo,fin_contrato',
            'indemnizacion' => 'nullable|numeric|min:0',
            'deducciones' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'El empleado es obligatorio.',
            'jornada_laboral_id.required' => 'La jornada laboral es obligatoria.',
            'fecha_retiro.required' => 'La fecha de retiro es obligatoria.',
            'fecha_retiro.before_or_equal' => 'La fecha de retiro no puede ser futura.',
            'motivo_retiro.required' => 'El motivo de retiro es obligatorio.',
            'motivo_retiro.in' => 'El motivo de retiro no es válido.',
            'indemnizacion.min' => 'La indemnización no puede ser negativa.',
            'deducciones.min' => 'Las deducciones finales no pueden ser negativas.',
        ];
    }
}
