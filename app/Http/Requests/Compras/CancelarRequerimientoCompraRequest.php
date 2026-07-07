<?php

namespace App\Http\Requests\Compras;

use Illuminate\Foundation\Http\FormRequest;

class CancelarRequerimientoCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.string' => 'El motivo debe ser una cadena de texto.',
            'motivo.max' => 'El motivo no debe exceder los 1000 caracteres.',
        ];
    }
}
