<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfiguracionNominaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'nullable|string|max:120',
            'porcentaje_salud_empleado' => 'required|numeric|min:0|max:100',
            'porcentaje_pension_empleado' => 'required|numeric|min:0|max:100',
            'porcentaje_salud_empleador' => 'required|numeric|min:0|max:100',
            'porcentaje_pension_empleador' => 'required|numeric|min:0|max:100',
            'porcentaje_arl' => 'required|numeric|min:0|max:100',
            'porcentaje_sena' => 'required|numeric|min:0|max:100',
            'porcentaje_icbf' => 'required|numeric|min:0|max:100',
            'porcentaje_caja_compensacion' => 'required|numeric|min:0|max:100',
            'recargo_extra_diurna' => 'required|numeric|min:0|max:5',
            'recargo_extra_nocturna' => 'required|numeric|min:0|max:5',
            'recargo_festiva' => 'required|numeric|min:0|max:5',
            'recargo_nocturna_festiva' => 'required|numeric|min:0|max:5',
            'porcentaje_incapacidad' => 'required|numeric|min:0|max:1',
            'hora_inicio_nocturna' => 'required|date_format:H:i',
            'hora_fin_nocturna' => 'required|date_format:H:i',
            'status' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'porcentaje_salud_empleado.required' => 'El porcentaje de salud es obligatorio.',
            'porcentaje_salud_empleado.numeric' => 'El porcentaje de salud debe ser numérico.',
            'porcentaje_pension_empleado.required' => 'El porcentaje de pensión es obligatorio.',
            'porcentaje_pension_empleado.numeric' => 'El porcentaje de pensión debe ser numérico.',
            'porcentaje_salud_empleador.required' => 'El porcentaje de salud empleador es obligatorio.',
            'porcentaje_pension_empleador.required' => 'El porcentaje de pensión empleador es obligatorio.',
            'porcentaje_arl.required' => 'El porcentaje de ARL es obligatorio.',
            'porcentaje_sena.required' => 'El porcentaje de SENA es obligatorio.',
            'porcentaje_icbf.required' => 'El porcentaje de ICBF es obligatorio.',
            'porcentaje_caja_compensacion.required' => 'El porcentaje de caja de compensación es obligatorio.',
            'recargo_extra_diurna.required' => 'El recargo extra diurno es obligatorio.',
            'recargo_extra_nocturna.required' => 'El recargo extra nocturno es obligatorio.',
            'recargo_festiva.required' => 'El recargo festivo es obligatorio.',
            'recargo_nocturna_festiva.required' => 'El recargo nocturno festivo es obligatorio.',
            'porcentaje_incapacidad.required' => 'El porcentaje reconocido por incapacidad es obligatorio.',
            'porcentaje_incapacidad.max' => 'El porcentaje de incapacidad debe estar entre 0 y 1.',
            'hora_inicio_nocturna.date_format' => 'La hora de inicio nocturna debe tener formato HH:mm.',
            'hora_fin_nocturna.date_format' => 'La hora de fin nocturna debe tener formato HH:mm.',
        ];
    }
}
