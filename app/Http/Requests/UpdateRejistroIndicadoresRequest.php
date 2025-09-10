<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRejistroIndicadoresRequest extends FormRequest
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
            'indicador_id' => 'sometimes|exists:indicadores,id',
            'fecha' => 'sometimes|date',
            'valor' => 'sometimes|numeric',
            'observaciones' => 'nullable|string',
            'documento' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'indicador_id.exists' => 'El indicador seleccionado no existe.',
            'fecha.date' => 'La fecha no es válida.',
            'valor.numeric' => 'El valor debe ser un número.',
            'documento.file' => 'El documento debe ser un archivo válido.',
            'documento.mimes' => 'El documento debe ser un archivo de tipo: pdf, doc, docx, xls, xlsx, png, jpg, jpeg.',
            'documento.max' => 'El documento no debe superar los 2MB.',
        ];
    }
}
