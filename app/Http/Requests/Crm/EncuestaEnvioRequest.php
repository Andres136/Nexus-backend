<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class EncuestaEnvioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_ids'   => 'required|array|min:1',
            'cliente_ids.*' => 'required|integer|exists:clientes,id',
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_ids.required'   => 'Selecciona al menos un cliente',
            'cliente_ids.min'        => 'Selecciona al menos un cliente',
            'cliente_ids.*.exists'   => 'Uno o más clientes no existen',
        ];
    }
}
