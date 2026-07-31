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
            'apellidos' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email',
            'telefono' => 'required|string',
            'password' => 'required|string|min:8',
            'role_id' => 'required|integer|exists:roles,id',
            'departamento_id' => 'required|integer|exists:departamentos,id',
            'sede_id' => 'required|integer|exists:sedes,id',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'foto_perfil' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
           // Reglas para la sede
        'sede_nombre' => 'nullable|string|max:255',
        'sede_direccion' => 'nullable|string|max:255',
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es requerido',
            'apellidos.string' => 'Los apellidos deben ser texto',
            'apellidos.max' => 'Los apellidos no deben superar 255 caracteres',
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
            'sede_id.required' => 'La sede es requerida',
            'sede_id.exists' => 'La sede seleccionada no es válida',
            'imagen.image' => 'La imagen de uso interno debe ser un archivo de imagen',
            'imagen.mimes' => 'La imagen de uso interno debe ser JPG, PNG, GIF o WEBP',
            'imagen.max' => 'La imagen de uso interno no debe exceder los 2 MB',
            'foto_perfil.image' => 'La foto de perfil debe ser una imagen',
            'foto_perfil.mimes' => 'La foto de perfil debe ser de tipo JPG, PNG, GIF o WEBP',
            'foto_perfil.max' => 'La foto de perfil no debe exceder los 2 MB',
       
            'sede_nombre.string' => 'El nombre de la sede debe ser una cadena de texto',
            'sede_direccion.string' => 'La dirección de la sede debe ser una cadena de texto',

  
        ];
    }
}
