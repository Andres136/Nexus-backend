<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentoRequest extends FormRequest
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
            'documento' => 'required|mimes:pdf,doc,docx,xls,xlsx', 
            'proceso_id' => 'required|integer',
            'user_id' => 'required|integer',
            'version' => 'required|string'
        ];
    }
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es requerido',
            'documento.required' => 'El documento es requerido',
            'documento.mimes' => 'El documento debe ser un archivo PDF, DOC o DOCX',
            'proceso_id.required' => 'El proceso es requerido',
            'proceso_id.integer' => 'El proceso debe ser un número entero',
            'user_id.required' => 'El usuario es requerido',
            'user_id.integer' => 'El usuario debe ser un número entero',
            'version.required' => 'La versión es requerida'
        ];
    }
}
