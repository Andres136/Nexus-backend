<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDescuentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto'              => 'sometimes|numeric|min:0',
            'inicio'             => 'sometimes|date',
            'fin'                => 'sometimes|nullable|date|after:inicio',
            'status'             => 'sometimes|boolean',
            'concepto_descuento' => 'sometimes|string|max:45',
        ];
    }

    public function messages(): array
    {
        return [
            'monto.numeric'          => 'El monto debe ser un número',
            'monto.min'              => 'El monto no puede ser negativo',
            'fin.after'              => 'La fecha fin debe ser después del inicio',
            'concepto_descuento.max' => 'El concepto no puede superar 45 caracteres',
        ];
    }
}