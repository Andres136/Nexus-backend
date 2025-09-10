<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistroIndicadoresRequest extends FormRequest
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
            'indicador_id' => 'required|exists:indicadores_procesos,id',
            'valor' => 'required|numeric',
            'fecha' => 'required|date',
            'observaciones' => 'nullable|string',
            'documento' => 'required|file|max:5048|mimes:pdf,jpg,jpeg,png',
      

            
        ];

    }


    public function messages(): array
    {
        return [
            'indicador_id.required' => 'El campo indicador es obligatorio.',
            'indicador_id.exists' => 'El indicador seleccionado no existe.',
            'valor.required' => 'El campo valor es obligatorio.',
            'valor.numeric' => 'El campo valor debe ser un número.',
            'fecha.required' => 'El campo fecha es obligatorio.',
            'fecha.date' => 'El campo fecha debe ser una fecha válida.',
            'observaciones.string' => 'El campo observaciones debe ser una cadena de texto.',
            'documento.required' => 'Debes subir un archivo como análisis.',
            'documento.file' => 'El campo documento debe ser un archivo.',
            'documento.max' => 'El archivo no debe exceder los 5 MB.',
            'documento.mimes' => 'El archivo debe ser un PDF, JPG, JPEG o PNG.',
         
        ];
    }
}
