<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class InspeccionUpdateRequest extends FormRequest
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
            'fecha' => 'required|date',
            'fecha_realizado' => 'nullable|date',
            'responsable' => 'required|string|max:255',
            'observaciones' => 'required|string|max:1000',
            'estado_general' => 'required|string|max:255',
            'documento' => 'nullable|file|mimes:pdf,doc,docx,xlsx,xls|max:10240',
        ];
    }

    public function messages()
    {
        return [
            'vehiculo_id.required' => 'Debes seleccionar un vehiculo.',
            'vehiculo_id.exists' => 'El vehiculo_id no existe en la base de datos.',
            'fecha.required' => 'Debes seleccionar la fecha de la inspección.',
            'responsable.required' => 'Debes ingresar el responsable.',
            'observaciones.required' => 'Debes ingresar las observaciones.',
            'estado_general.required' => 'Selecciona un estado general.',
            'documento.file' => 'El documento debe ser un archivo.',
            'documento.mimes' => 'El documento debe ser un archivo PDF, DOC o DOCX, XLS o XLSX',
            'documento.max' => 'El documento no debe exceder los 10MB de tamaño.',
        ];
    }
}
