<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNominaConceptoContableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'string', 'max:160'],
            'tipo' => ['sometimes', Rule::in(['devengo', 'deduccion', 'aporte_empleador', 'provision', 'neto'])],
            'clasificacion_nomina' => ['sometimes', Rule::in(['salarial', 'pago_no_salarial', 'bonificacion', 'prestacion', 'aporte', 'deduccion', 'otro'])],
            'puck_id' => ['sometimes', 'nullable', 'integer', 'exists:puck,id'],
            'naturaleza' => ['sometimes', Rule::in(['debito', 'credito'])],
            'afecta_base_aportes' => ['sometimes', 'boolean'],
            'afecta_prestaciones' => ['sometimes', 'boolean'],
            'es_pago_no_salarial' => ['sometimes', 'boolean'],
            'requiere_tercero' => ['sometimes', 'boolean'],
            'requiere_centro_costo' => ['sometimes', 'boolean'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'puck_id.exists' => 'La cuenta PUC seleccionada no existe.',
            'naturaleza.in' => 'La naturaleza debe ser débito o crédito.',
            'tipo.in' => 'El tipo de concepto contable no es válido.',
            'clasificacion_nomina.in' => 'La clasificación de nómina no es válida.',
        ];
    }
}
