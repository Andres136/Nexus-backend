<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmpresaRequest extends FormRequest
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
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'nit' => 'nullable|string|max:100|unique:empresas,nit,' . $this->route('empresa'),
            'logo' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no debe exceder los 255 caracteres.',
            'direccion.string' => 'La dirección debe ser una cadena de texto.',
            'direccion.max' => 'La dirección no debe exceder los 255 caracteres.',
            'telefono.string' => 'El teléfono debe ser una cadena de texto.',
            'telefono.max' => 'El teléfono no debe exceder los 50 caracteres.',
            'email.email' => 'El correo electrónico debe ser una dirección válida.',
            'email.max' => 'El correo electrónico no debe exceder los 255 caracteres.',
            'nit.string' => 'El NIT debe ser una cadena de texto.',
            'nit.max' => 'El NIT no debe exceder los 100 caracteres.',
            'nit.unique' => 'El NIT ya está en uso por otra empresa.',
            'logo.file' => 'El logo debe ser un archivo válido.',
            'logo.mimes' => 'El logo debe ser un archivo de tipo: jpeg, png, jpg.',
            'logo.max' => 'El logo no debe exceder los 2MB.',
        ];
    }
}
