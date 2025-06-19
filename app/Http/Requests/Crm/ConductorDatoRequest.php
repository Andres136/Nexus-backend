<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class ConductorDatoRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id',
            'cedula' => 'required|string|max:20',
            'licencia_conduccion' => 'required|string|max:50',
            'tipo_licencia' => 'required|string|max:50',
            'fecha_expedicion' => 'required|date_format:Y-m-d',
            'fecha_vencimiento' => 'required|date_format:Y-m-d|after_or_equal:fecha_expedicion',
            'categoria' => 'nullable|string|max:20',
            'grupo_sanguineo' => 'nullable|string|max:10',
            'rut_archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5012', // Máximo 5MB
            'licencia_archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5012', // Máximo 5MB
            'comparendo_archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5012', // Máximo 5MB
        ];
    }


    public function messages(): array
    {
        return [
            'user_id.required' => 'El usuario es obligatorio.',
            'user_id.exists' => 'El usuario seleccionado no existe.',
            'cedula.required' => 'La cédula es obligatoria.',
            'cedula.string' => 'La cédula debe ser una cadena de texto.',
            'cedula.max' => 'La cédula no puede tener más de 20 caracteres.',
            'licencia_conduccion.required' => 'La licencia de conducción es obligatoria.',
            'licencia_conduccion.string' => 'La licencia de conducción debe ser una cadena de texto.',
            'licencia_conduccion.max' => 'La licencia de conducción no puede tener más de 50 caracteres.',
            'tipo_licencia.required' => 'El tipo de licencia es obligatorio.',
            'tipo_licencia.string' => 'El tipo de licencia debe ser una cadena de texto.',
            'tipo_licencia.max' => 'El tipo de licencia no puede tener más de 50 caracteres.',
            'fecha_expedicion.required' => 'La fecha de expedición es obligatoria.',
            'fecha_expedicion.date_format' => 'La fecha de expedición debe tener el formato Y-m-d.',
            'fecha_vencimiento.required' => 'La fecha de vencimiento es obligatoria.',
            'fecha_vencimiento.date_format' => 'La fecha de vencimiento debe tener el formato Y-m-d.',
            'fecha_vencimiento.after_or_equal' => 'La fecha de vencimiento debe ser igual o posterior a la fecha de expedición.',
            'categoria.string' => 'La categoría debe ser una cadena de texto.',
            'categoria.max' => 'La categoría no puede tener más de 20 caracteres.',
            'grupo_sanguineo.string' => 'El grupo sanguíneo debe ser una cadena de texto.',
            'grupo_sanguineo.max' => 'El grupo sanguíneo no puede tener más de 10 caracteres.',
            'rut_archivo.file' => 'El archivo del RUT debe ser un archivo.',
            'rut_archivo.mimes' => 'El archivo del RUT debe ser un archivo de tipo PDF, JPG, JPEG o PNG.',
            'rut_archivo.max' => 'El archivo del RUT no puede exceder los 5MB.',
            'licencia_archivo.file' => 'El archivo de la licencia debe ser un archivo.',
            'licencia_archivo.mimes' => 'El archivo de la licencia debe ser un archivo de tipo PDF, JPG, JPEG o PNG.',
            'licencia_archivo.max' => 'El archivo de la licencia no puede exceder los 5MB.',
            'comparendo_archivo.file' => 'El archivo del comparendo debe ser un archivo.',
            'comparendo_archivo.mimes' => 'El archivo del comparendo debe ser un archivo de tipo PDF, JPG, JPEG o PNG.',
            'comparendo_archivo.max' => 'El archivo del comparendo no puede exceder los 5MB.',
        ];

    }
}
