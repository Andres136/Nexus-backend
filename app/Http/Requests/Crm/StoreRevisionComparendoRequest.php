<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class StoreRevisionComparendoRequest extends FormRequest
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
            'conductor_id' => 'required|exists:datos_conductores,id',
            'fecha_revision' => 'required|date',
            'archivo_soporte' => 'required|file|mimes:pdf,jpg,jpeg,png|max:51200', // 5MB
            'observaciones' => 'nullable|string|max:1000',
        ];
    }
    public function messages(): array
    {
        return [
            'conductor_id.required' => 'El campo conductor es obligatorio.',
            'conductor_id.exists' => 'El conductor seleccionado no existe.',
            'fecha_revision.required' => 'La fecha de revisión es obligatoria.',
            'fecha_revision.date' => 'La fecha de revisión debe ser una fecha válida.',
            'archivo_soporte.required' => 'El archivo de soporte es obligatorio.',
            'archivo_soporte.file' => 'El archivo de soporte debe ser un archivo válido.',
            'archivo_soporte.mimes' => 'El archivo de soporte debe ser un archivo PDF, JPG, JPEG o PNG.',
            'archivo_soporte.max' => 'El archivo de soporte no puede exceder los 5MB.',
            'observaciones.string' => 'Las observaciones deben ser un texto válido.',
            'observaciones.max' => 'Las observaciones no pueden exceder los 1000 caracteres.',
        ];
    }
}
