<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrdenTrabajoRequest extends FormRequest
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
        return [
            'observaciones' => ['required', 'string'],
                'sede_id' => [
                'required', // Puede venir vacío si ya se resolverá en el controlador
                'integer',
                Rule::exists('sedes', 'id'), // Debe existir en la tabla sedes
            ],
            
        ];
    }
    public function messages(): array
    {
        return [
            'observaciones.required' => 'El campo observaciones es obligatorio',
            'observaciones.string' => 'El campo observaciones debe ser una cadena de texto',
            'sede_id.required' => 'El campo sede es obligatorio',
            'sede_id.integer' => 'El campo sede debe ser un número entero',
        ];
    }
}
