<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StoreComisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'periodo_inicio' => 'required|date',
            'periodo_fin' => 'required|date|after_or_equal:periodo_inicio',
            'concepto' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0.01',
            'observacion' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'El empleado es obligatorio.',
            'user_id.exists' => 'El empleado seleccionado no existe.',
            'periodo_inicio.required' => 'El inicio del período es obligatorio.',
            'periodo_fin.required' => 'El fin del período es obligatorio.',
            'periodo_fin.after_or_equal' => 'El fin debe ser igual o posterior al inicio.',
            'concepto.required' => 'El concepto de la comisión es obligatorio.',
            'valor.required' => 'El valor de la comisión es obligatorio.',
            'valor.min' => 'La comisión debe ser mayor que cero.',
        ];
    }
}
