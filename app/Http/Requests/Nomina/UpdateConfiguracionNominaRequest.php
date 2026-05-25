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
        ];
    }
}
