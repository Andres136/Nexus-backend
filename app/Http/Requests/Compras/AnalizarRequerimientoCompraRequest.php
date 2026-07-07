<?php

namespace App\Http\Requests\Compras;

use Illuminate\Foundation\Http\FormRequest;

class AnalizarRequerimientoCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comentario' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'comentario.string' => 'El comentario debe ser una cadena de texto.',
            'comentario.max' => 'El comentario no debe exceder los 1000 caracteres.',
        ];
    }
}
