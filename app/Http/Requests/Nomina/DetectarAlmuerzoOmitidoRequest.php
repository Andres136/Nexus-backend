<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class DetectarAlmuerzoOmitidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id'     => $this->query('user_id'),
            'fecha_desde' => $this->query('fecha_desde'),
            'fecha_hasta' => $this->query('fecha_hasta'),
        ]);
    }

    public function rules(): array
    {
        return [
            'user_id'     => 'required|integer|exists:users,id',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Debes seleccionar un empleado.',
            'user_id.exists'   => 'El empleado seleccionado no existe.',
            'fecha_hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
        ];
    }
}
