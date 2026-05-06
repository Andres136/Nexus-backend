<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class UpdateValorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'valor_hora_normal'          => 'sometimes|numeric|min:0',
            'valor_hora_nocturna'        => 'sometimes|numeric|min:0',
            'valor_hora_dominical'       => 'sometimes|numeric|min:0',
            'valor_hora_dominical_extra' => 'sometimes|numeric|min:0',
            'status'                     => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'valor_hora_normal.numeric'          => 'El valor debe ser un número',
            'valor_hora_nocturna.numeric'        => 'El valor debe ser un número',
            'valor_hora_dominical.numeric'       => 'El valor debe ser un número',
            'valor_hora_dominical_extra.numeric' => 'El valor debe ser un número',
            'valor_hora_normal.min'              => 'El valor no puede ser negativo',
        ];
    }
}
