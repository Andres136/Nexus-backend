<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistroRequest extends FormRequest
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
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'telefono' => 'required|string',
            'password' => 'required|string',
            'role_id' => 'required',
            'departamento_id' => 'required',
         
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es requerido',
            'email.required' => 'El email es requerido',
            'email.email' => 'El email no es válido',
            'email.unique' => 'El email ya está registrado',
            'password.required' => 'La contraseña es requerida',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.letters' => 'La contraseña debe contener al menos una letra.',
            'password.symbols' => 'La contraseña debe incluir al menos un símbolo.',
            'password.numbers' => 'La contraseña debe contener al menos un número.',
            'telefono.required' => 'El teléfono es requerido',
            

            'role_id.required' => 'El rol es requerido',
            'departamento_id.required' => 'El departamento es requerido',
            
  
        ];
    }
}
