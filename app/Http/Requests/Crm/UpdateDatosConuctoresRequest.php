<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDatosConuctoresRequest extends FormRequest
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
            //Actualizar los campos rut_archivo, licencia_archivo y comparendo_archivo
           
            'grupo_sanguineo' => 'nullable|string|max:10',
            'rut_archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5014', // Máximo 5MB
            'licencia_archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5014', // Máximo 5MB
            'comparendo_archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5014', // Máximo 5MB
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
           
            'rut_archivo.file' => 'El archivo de RUT debe ser un archivo válido.',
            'rut_archivo.mimes' => 'El archivo de RUT debe ser un archivo de tipo PDF, JPG, JPEG o PNG.',
            'rut_archivo.max' => 'El archivo de RUT no puede exceder los 5MB.',
            'licencia_archivo.file' => 'El archivo de licencia debe ser un archivo válido.',
            'licencia_archivo.mimes' => 'El archivo de licencia debe ser un archivo de tipo PDF, JPG, JPEG o PNG.',
            'licencia_archivo.max' => 'El archivo de licencia no puede exceder los 5MB.',
            'comparendo_archivo.file' => 'El archivo de comparendo debe ser un archivo válido.',
            'comparendo_archivo.mimes' => 'El archivo de comparendo debe ser un archivo de tipo PDF, JPG, JPEG o PNG.',
            'comparendo_archivo.max' => 'El archivo de comparendo no puede exceder los 5MB.',   
  
        ];
}
}