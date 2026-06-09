<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNovedadRetroactivaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'fecha_origen' => ['required', 'date'],
            'aplicar_desde' => ['required', 'date'],
            'aplicar_hasta' => ['nullable', 'date', 'after_or_equal:aplicar_desde'],
            'tipo' => ['required', Rule::in(['devengo', 'deduccion'])],
            'concepto' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'observacion' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'El empleado es obligatorio.',
            'user_id.exists' => 'El empleado seleccionado no existe.',
            'fecha_origen.required' => 'La fecha de origen es obligatoria.',
            'aplicar_desde.required' => 'La fecha desde la que aplica es obligatoria.',
            'aplicar_hasta.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
            'tipo.in' => 'El tipo debe ser devengo o deducción.',
            'concepto.required' => 'El concepto es obligatorio.',
            'valor.required' => 'El valor es obligatorio.',
            'valor.min' => 'El valor debe ser mayor que cero.',
        ];
    }
}
