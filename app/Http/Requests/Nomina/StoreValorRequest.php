<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StoreValorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'valor_hora_normal'          => 'required|numeric|min:0',
            'valor_hora_nocturna'        => 'required|numeric|min:0',
            'valor_hora_dominical'       => 'required|numeric|min:0',
            'valor_hora_dominical_extra' => 'required|numeric|min:0',
            'status'                     => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'valor_hora_normal.required'          => 'El valor hora normal es obligatorio',
            'valor_hora_nocturna.required'        => 'El valor hora nocturna es obligatorio',
            'valor_hora_dominical.required'       => 'El valor hora dominical es obligatorio',
            'valor_hora_dominical_extra.required' => 'El valor hora dominical extra es obligatorio',
            'valor_hora_normal.numeric'           => 'El valor debe ser un número',
            'valor_hora_normal.min'               => 'El valor no puede ser negativo',
        ];
    }
}
