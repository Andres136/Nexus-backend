<?php

namespace App\Http\Requests\Calidad;

use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateNovedadesRequest extends FormRequest
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
            'estado' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'soporte' => 'nullable|required_if:estado,!=,Resuelto|file|mimes:jpg,jpeg,png,pdf,doc,docx,xlsx|max:1024',
            'fecha_revision' => 'nullable|date',
            'fecha_terminado' => 'nullable|date',
            'responsable_revision' => 'nullable|string|max:255',
            'fecha_estado' => 'nullable|date',
            'fuentes' => 'nullable|string|max:255',
          'causa' => 'nullable|string|max:10000',
        ];

    }

    public function messages()
    {
        return [
            'estado.required' => 'El campo estado es obligatorio.',
            'estado.string' => 'El campo estado debe ser una cadena de texto.',
            'estado.max' => 'El campo estado no debe exceder los 255 caracteres.',
            'descripcion.string' => 'El campo descripción debe ser una cadena de texto.',
            'soporte.required_if' => 'El campo soporte es obligatorio cuando el estado no es Resuelto.',
            'soporte.file' => 'El campo soporte debe ser un archivo.',
            'soporte.mimes' => 'El campo soporte debe ser un archivo de tipo jpg, jpeg, png, pdf, doc, docx o xlsx.',
            'soporte.max' => 'El campo soporte no debe exceder los 1024 kilobytes.',
            'fecha_revision.date' => 'El campo fecha de revisión debe ser una fecha válida.',
            'responsable_revision.string' => 'El campo responsable de revisión debe ser una cadena de texto.',
            'responsable_revision.max' => 'El campo responsable de revisión no debe exceder los 255 caracteres.',
            'fecha_estado.date' => 'El campo fecha de estado debe ser una fecha válida.',
            'fuentes.string' => 'El campo fuentes debe ser una cadena de texto.',
            'fuentes.max' => 'El campo fuentes no debe exceder los 255 caracteres.',
            'causa.string' => 'El campo causa debe ser una cadena de texto.',
            'causa.max' => 'El campo causa no debe exceder los 10000 caracteres.',
        ];
    }
}
