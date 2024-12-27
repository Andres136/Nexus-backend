<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcesoRequest extends FormRequest
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
            'nombre' => 'required|string',
            'departamento_id' => 'required|integer',
            'user_id' => 'required|integer',
      
        ];
    }
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es requerido',
            'user_id.required' => 'El usuario es requerido',
            'departamento_id.required' => 'El departamento es requerido',
            'departamento_id.integer' => 'El departamento debe ser un número entero',
           
        ];
    }
}
