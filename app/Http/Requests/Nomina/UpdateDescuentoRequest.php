<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

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
            'numero_cuotas'      => 'sometimes|integer|min:1',
            'frecuencia_pago'    => 'sometimes|in:quincenal,mensual',
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

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}