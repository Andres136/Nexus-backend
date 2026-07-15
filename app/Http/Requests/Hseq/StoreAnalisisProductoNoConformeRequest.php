<?php

namespace App\Http\Requests\Hseq;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnalisisProductoNoConformeRequest extends FormRequest
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
            'producto_no_conforme_id' => 'required|exists:productos_no_conformes,id',
            'fecha_analisis' => 'required|date',
            'causa_raiz' => 'required|string',
            'acciones_correctivas' => 'nullable|string',
            'acciones_preventivas' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'estado_id' => 'required|exists:estados,id',
            'fecha_cierre' => 'nullable|date',
            'archivo_evidencia' => 'nullable|file|mimes:pdf,jpg,jpeg,png,docx,xlsx|max:10240',
            'responsable_cierre_id' => 'nullable|exists:users,id'
        ];
    }


    public function messages()
    {
        return [
            'producto_no_conforme_id.required' => 'El ID del producto no conforme es obligatorio.',
            'producto_no_conforme_id.exists' => 'El producto no conforme especificado no existe.',
            'fecha_analisis.required' => 'La fecha de análisis es obligatoria.',
            'fecha_analisis.date' => 'La fecha de análisis debe ser una fecha válida.',
            'causa_raiz.required' => 'La causa raíz es obligatoria.',
            'causa_raiz.string' => 'La causa raíz debe ser una cadena de texto.',
            'acciones_correctivas.string' => 'Las acciones correctivas deben ser una cadena de texto.',
            'acciones_preventivas.string' => 'Las acciones preventivas deben ser una cadena de texto.',
            'observaciones.string' => 'Las observaciones deben ser una cadena de texto.',
            'estado_id.required' => 'El ID del estado es obligatorio.',
            'estado_id.exists' => 'El estado especificado no existe.',
            'fecha_cierre.date' => 'La fecha de cierre debe ser una fecha válida.',
            'archivo_evidencia.file'   => 'El archivo de evidencia debe ser un archivo válido.',
            'archivo_evidencia.mimes'  => 'El archivo debe ser pdf, jpg, jpeg, png, docx o xlsx.',
            'archivo_evidencia.max'    => 'El archivo no debe superar los 10MB.',
            'responsable_cierre_id.exists' => 'El responsable de cierre especificado no existe.'
        ];
    }
}
