<?php

namespace App\Http\Requests\Hseq;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportesBicRequest extends FormRequest
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
            'empresa_id' => 'required|integer|exists:empresas,id',
            'nombre' => 'required|string|max:255',
            'fecha_reporte' => 'required|date',
            'archivo' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:102400' // 100MB
        ];
    }

    public function messages(): array
    {
        return [
            'empresa_id.required' => 'El campo empresa_id es obligatorio.',
            'empresa_id.integer' => 'El campo empresa_id debe ser un número entero.',
            'empresa_id.exists' => 'La empresa seleccionada no existe.',
            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.string' => 'El campo nombre debe ser una cadena de texto.',
            'nombre.max' => 'El campo nombre no puede exceder los 255 caracteres.',
            'fecha_reporte.required' => 'El campo fecha_reporte es obligatorio.',
            'fecha_reporte.date' => 'El campo fecha_reporte debe ser una fecha válida.',
            'archivo.file' => 'El campo archivo debe ser un archivo válido.',
            'archivo.mimes' => 'El campo archivo debe ser un archivo de tipo: pdf, doc, docx, xls, xlsx.',
            'archivo.max' => 'El campo archivo no puede exceder los 100MB.'
        ];
    }
}
