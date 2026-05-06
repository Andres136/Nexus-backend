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
            'monto'              => 'required|numeric|min:0',
            'inicio'             => 'required|date',
            'fin'                => 'nullable|date|after:inicio',
            'status'             => 'boolean',
            'concepto_descuento' => 'required|string|max:45',
            // user_id viene del usuario autenticado — no del request
        ];
    }

    public function messages(): array
    {
        return [
            'monto.required'              => 'El monto es obligatorio',
            'monto.numeric'               => 'El monto debe ser un número',
            'monto.min'                   => 'El monto no puede ser negativo',
            'inicio.required'             => 'La fecha de inicio es obligatoria',
            'fin.after'                   => 'La fecha fin debe ser después del inicio',
            'concepto_descuento.required' => 'El concepto del descuento es obligatorio',
            'concepto_descuento.max'      => 'El concepto no puede superar 45 caracteres',
        ];
    }
}