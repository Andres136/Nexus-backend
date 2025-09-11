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
            'documento' => 'nullable|file|max:5048|mimes:doc,docx,xls,xlsx',
      

            
        ];

    }

    public function withValidator($validator)
{
    $validator->after(function ($validator) {
        $observaciones = $this->input('observaciones');
        $documento = $this->file('documento');

        // Si no hay observaciones ni documento, error
        if (empty($observaciones) && !$documento) {
            $validator->errors()->add('observaciones', 'Debes ingresar observaciones o subir un documento.');
            $validator->errors()->add('documento', 'Debes ingresar observaciones o subir un documento.');
        }

        // Si las observaciones son muy largas (>500 caracteres), documento es obligatorio
        if (!empty($observaciones) && strlen($observaciones) > 500 && !$documento) {
            $validator->errors()->add('documento', 'Si las observaciones son muy extensas, debes adjuntar un documento.');
        }
    });
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
            'documento.max' => 'El archivo no debe superar los 5MB.',
            'documento.mimes' => 'El archivo debe ser un documento Word (.doc, .docx) o Excel (.xls, .xlsx).',
            
         
        ];
    }
}
