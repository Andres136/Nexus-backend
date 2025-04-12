<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class DocumentosAdministrativoRequest extends FormRequest
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
            'archivo' => 'required|mimes:pdf,doc,docx,xls,xlsx|max:5120', 
            'carpeta_id' => 'required|exists:carpetas,id',
   
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El campo nombre es requerido',
            'nombre.string' => 'El campo nombre debe ser una cadena de texto',
            'nombre.max' => 'El campo nombre no debe exceder los 255 caracteres',
            'archivo.required' => 'El campo archivo es requerido',
            'archivo.mimes' => 'El archivo debe ser un archivo PDF, DOC o DOCX, XLS o XLSX',
            'archivo.max' => 'El archivo no debe exceder los 5MB',
            'archivo.mimes' => 'El archivo debe ser un archivo PDF, DOC o DOCX, XLS o XLSX',
            'carpeta_id.required' => 'El campo carpeta es requerido',
            'carpeta_id.exists' => 'La carpeta seleccionada no existe',
           
          
        ];
    }
}
