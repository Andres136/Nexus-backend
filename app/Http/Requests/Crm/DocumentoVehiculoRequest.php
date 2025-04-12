<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class DocumentoVehiculoRequest extends FormRequest
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
            'vehiculo_id' => 'required|exists:vehiculos,id',
            'tipo_documento' => 'required|string|max:255',
            'fecha_vencimiento' => 'required|date',
            'fecha_renovacion' => 'required|date',
            'documento_pdf' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5000',
            'estado' => 'required|',
        ];
    }

    public function messages()
    {
        return [
            'vehiculo_id.required' => 'El campo vehiculo_id es obligatorio.',
            'vehiculo_id.exists' => 'El vehiculo_id no existe en la base de datos.',
            'tipo_documento.required' => 'El campo tipo_documento es obligatorio.',
            'tipo_documento.string' => 'El campo tipo_documento debe ser una cadena de texto.',
            'tipo_documento.max' => 'El campo tipo_documento no puede tener más de 255 caracteres.',
            'fecha_vencimiento.required' => 'El campo fecha_vencimiento es obligatorio.',
            'fecha_vencimiento.date' => 'El campo fecha_vencimiento debe ser una fecha válida.',
            'fecha_renovacion.required' => 'Debes agregar la fecha de renovacion.',
            'fecha_renovacion.date' => 'El campo fecha_renovacion debe ser una fecha válida.',
            'documento_pdf.required' => 'El campo  es obligatorio.',
            'documento_pdf.file' => 'El campo documento_pdf debe ser un archivo.',
            'documento_pdf.mimes' => 'El campo documento_pdf debe ser un archivo de tipo: pdf, jpg, jpeg, png.',
            'documento_pdf.max' => 'El campo documento_pdf no puede tener más de 5MB.',
            'estado.required' => 'El campo estado es obligatorio.',
        ];
    }
}
