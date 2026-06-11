<?php

namespace App\Http\Requests\contabilidad;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePuckRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
public function rules(): array
{
    $puckId = $this->route('cuentas_contable');

    return [
        'nombre' => 'required|string|max:255',
        'numero' => [
            'required',
            'string',
            'max:20',
            'regex:/^\d+$/',
            Rule::unique('puck', 'numero')->ignore($puckId),
        ],
        'naturaleza' => 'nullable|in:debito,credito',
        'descripcion' => 'nullable|string',
        'dinamica' => 'nullable|string',
        'permite_movimiento' => 'sometimes|boolean',
        'activo' => 'sometimes|boolean',
    ];
}

    public function messages()
    {
        return [
            'nombre.required' => 'El nombre del puck es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
            'numero.required' => 'El código de la cuenta es obligatorio.',
            'numero.unique' => 'El código de la cuenta ya existe.',
            'numero.regex' => 'El código de la cuenta solo puede contener números.',
            'naturaleza.in' => 'La naturaleza debe ser débito o crédito.',
        ];
    }   
}
