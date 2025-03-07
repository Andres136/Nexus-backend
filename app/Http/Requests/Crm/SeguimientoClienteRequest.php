<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class SeguimientoClienteRequest extends FormRequest
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
            //
           
            'user_id' => 'required|integer',
            'tipo_contacto' => 'required|string',
            'estado' => 'required|string',
            'comentario' => 'required|string',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
  
            'user_id.required' => 'El id del usuario es requerido',
            'tipo_contacto.required' => 'El tipo de contacto es requerido',
            'estado.required' => 'El estado es requerido',
            'comentario.required' => 'El comentario es requerido',
        ];
    }
}
