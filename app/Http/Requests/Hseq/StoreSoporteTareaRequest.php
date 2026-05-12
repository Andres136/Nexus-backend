<?php

namespace App\Http\Requests\Hseq;

use Illuminate\Foundation\Http\FormRequest;

class StoreSoporteTareaRequest extends FormRequest
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
        // 🔹 Múltiples archivos
        'soporte_tarea' => 'nullable|array',

        // 🔹 Cada archivo individual
        'soporte_tarea.*' => 'file|mimes:pdf,jpg,jpeg,png,xlsx,xls,doc,docx|max:10240',

        'tarea_id'    => 'nullable|exists:tareas,id',
        'hallazgo_id' => 'nullable|exists:hallazgo_novedades,id',
    ];
}

public function messages(): array
{
    return [
        'soporte_tarea.array'     => 'Los soportes deben enviarse como una lista de archivos.',

        'soporte_tarea.*.file'    => 'Cada soporte debe ser un archivo válido.',
        'soporte_tarea.*.mimes'   => 'Cada soporte debe ser PDF, imagen, Excel o Word.',
        'soporte_tarea.*.max'     => 'Cada archivo no debe superar los 10MB.',

        'tarea_id.exists'         => 'La tarea seleccionada no existe.',
        'hallazgo_id.exists'      => 'El hallazgo seleccionado no existe.',
    ];
}
}
