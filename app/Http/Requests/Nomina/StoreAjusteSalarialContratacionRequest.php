<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAjusteSalarialContratacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contratacion_id' => ['required', 'integer', 'exists:contrataciones,id'],
            'tipo_ajuste' => ['required', Rule::in([
                'salario_minimo',
                'aumento_porcentual',
                'aumento_manual',
                'correccion',
                'promocion',
                'cambio_cargo',
            ])],
            'salario_nuevo' => ['required', 'numeric', 'min:0'],
            'auxilio_nuevo' => ['nullable', 'numeric', 'min:0'],
            'no_salarial_nuevo' => ['nullable', 'numeric', 'min:0'],
            'porcentaje_aumento' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'fecha_vigencia' => ['required', 'date'],
            'motivo' => ['nullable', 'string', 'max:160'],
            'observacion' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'contratacion_id.required' => 'Selecciona el contrato del empleado.',
            'salario_nuevo.required' => 'Ingresa el nuevo salario.',
            'fecha_vigencia.required' => 'Indica la fecha desde la cual aplica el ajuste.',
        ];
    }
}
