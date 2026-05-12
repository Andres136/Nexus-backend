<?php

namespace App\Http\Requests\Hseq;

use Illuminate\Foundation\Http\FormRequest;

class StoreHallazgoNovedadRequest extends FormRequest
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
            'novedad_id' => 'required|exists:novedad_diaria,id',
            'causa' => 'required|string',
            'plan_accion' => 'required|string',
            'responsable_id' => 'required|exists:users,id',
            'fecha_cierre' => 'required|date',
            'fecha_revision' => 'required|date',
            'estado' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'soporte_tarea' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,xls,doc,docx|max:10240',
        ];
    }


    public function messages()
    {
        return [
            'novedad_id.required' => 'El campo novedad es obligatorio.',
            'novedad_id.exists' => 'La novedad seleccionada no existe.',
            'causa.required' => 'El campo causa es obligatorio.',
            'plan_accion.required' => 'El campo plan de acción es obligatorio.',
            'responsable_id.required' => 'El campo responsable es obligatorio.',
            'responsable_id.exists' => 'El responsable seleccionado no existe.',
            'fecha_cierre.required' => 'El campo fecha de cierre es obligatorio.',
            'fecha_cierre.date' => 'El campo fecha de cierre debe ser una fecha válida.',
            'fecha_revision.required' => 'El campo fecha de revisión es obligatorio.',
            'fecha_revision.date' => 'El campo fecha de revisión debe ser una fecha válida.',
        ];
    }   
}
