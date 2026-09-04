<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class CorregirEntregaTotalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cantidad_total' => ['required', 'numeric', 'min:0'],
            'motivo' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'cantidad_total.required' => 'El total enviado es obligatorio.',
            'cantidad_total.numeric' => 'El total enviado debe ser un número.',
            'cantidad_total.min' => 'El total enviado no puede ser negativo.',
            'motivo.required' => 'Debe indicar el motivo de la corrección.',
            'motivo.min' => 'El motivo es demasiado corto.',
            'motivo.max' => 'El motivo no puede exceder los 500 caracteres.',
        ];
    }
}
