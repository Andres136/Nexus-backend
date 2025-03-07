<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

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
            
        ];
    }
    public function messages(): array
    {
        return [
            'observaciones.required' => 'El campo observaciones es obligatorio',
            'observaciones.string' => 'El campo observaciones debe ser una cadena de texto',
        ];
    }
}
