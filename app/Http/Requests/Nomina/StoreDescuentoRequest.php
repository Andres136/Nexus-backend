<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StoreDescuentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

public function rules(): array
{
    return [
        'user_id'            => 'required|exists:users,id',
        'monto'              => 'required|numeric|min:0',
        'inicio'             => 'required|date',
        'fin'                => 'nullable|date|after:inicio',
        'status'             => 'boolean',
        'concepto_descuento' => 'required|string|max:45',
    ];
}

public function messages(): array
{
    return [
        'user_id.required'            => 'El empleado es obligatorio',
        'user_id.exists'              => 'El empleado no existe',
        'monto.required'              => 'El monto es obligatorio',
        'monto.numeric'               => 'El monto debe ser un número',
        'monto.min'                   => 'El monto no puede ser negativo',
        'inicio.required'             => 'La fecha de inicio es obligatoria',
        'fin.after'                   => 'La fecha fin debe ser después del inicio',
        'concepto_descuento.required' => 'El concepto del descuento es obligatorio',
    ];
}
}