<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

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

        // Se calcula automáticamente
        'fin'                => 'nullable|date',

        'status'             => 'nullable|boolean',
        'concepto_descuento' => 'required|string|max:45',

        'numero_cuotas'      => 'required|integer|min:1',

        // Se calcula automáticamente
        'valor_cuota'        => 'nullable|numeric|min:0',

        'frecuencia_pago'    => 'required|in:quincenal,mensual',
    ];
}

public function messages(): array
{
    return [
        'user_id.required'            => 'El empleado es obligatorio.',
        'user_id.exists'              => 'El empleado no existe.',

        'monto.required'              => 'El monto es obligatorio.',
        'monto.numeric'               => 'El monto debe ser numérico.',
        'monto.min'                   => 'El monto no puede ser negativo.',

        'inicio.required'             => 'La fecha de inicio es obligatoria.',
        'inicio.date'                 => 'La fecha de inicio no es válida.',

        'fin.date'                    => 'La fecha final no es válida.',

        'concepto_descuento.required' => 'El concepto del descuento es obligatorio.',
        'concepto_descuento.max'      => 'El concepto no debe superar 45 caracteres.',

        'numero_cuotas.required'      => 'El número de cuotas es obligatorio.',
        'numero_cuotas.integer'       => 'El número de cuotas debe ser entero.',
        'numero_cuotas.min'           => 'Debe haber al menos una cuota.',

        'valor_cuota.numeric'         => 'El valor de cuota debe ser numérico.',
        'valor_cuota.min'             => 'El valor de cuota no puede ser negativo.',

        'frecuencia_pago.required'    => 'La frecuencia de pago es obligatoria.',
        'frecuencia_pago.in'          => 'La frecuencia debe ser quincenal o mensual.',
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