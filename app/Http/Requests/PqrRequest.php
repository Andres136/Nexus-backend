<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PqrRequest extends FormRequest
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
            'empresa' => 'required|string',
            'email' => 'required|email',
            'telefono' => 'required|string',
            'mensaje' => 'required|string',
            // Nuevas reglas de validación para las columnas añadidas
        'archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xlsx|max:5120', // 5MB

        ];
    }

    public function messages()
    {
        return [
            'nombre.required' => 'El campo nombre es obligatorio',
            'empresa.required' => 'El campo empresa es obligatorio',
            'email.required' => 'El campo email es obligatorio',
            'email.email' => 'El campo email debe ser un correo electrónico válido',
            'telefono.required' => 'El campo teléfono es obligatorio',
            'mensaje.required' => 'El campo mensaje es obligatorio',
            'archivo.file' => 'El archivo debe ser un archivo válido',
            'archivo.mimes' => 'El archivo debe ser de tipo: pdf, jpg, jpeg, png, doc, docx, xlsx',
            'archivo.max' => 'El archivo no debe exceder los 5MB',
        ];
    }
}
