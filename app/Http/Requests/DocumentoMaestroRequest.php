<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentoMaestroRequest extends FormRequest
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
            'departamento_id' => 'required|integer|exists:departamentos,id',
            'user_id' => 'required|integer|exists:users,id',
            'nombre' => 'required|string|max:255',
            'tipo_documento' => 'required|string|max:100|exists:tipos_documento_maestro,nombre',
            'codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('documentos_maestros', 'codigo')
                    ->where(fn ($query) => $query->where('departamento_id', $this->departamento_id))
                    ->ignore($this->route('documentos_maestro')),
            ],
            'fecha_emision' => 'required|date',
            'fecha_actualizacion' => 'nullable|date|after_or_equal:fecha_emision',
            'version' => 'required|integer|min:1',
            'medio_fisico' => 'nullable|string|max:255|required_without:medio_digital',
            'medio_digital' => 'nullable|url|max:255|required_without:medio_fisico',
            'retencion_gestion' => 'nullable|string|max:100',
            'retencion_central' => 'nullable|string|max:100',
            'disposicion_final' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'departamento_id.required' => 'El departamento es obligatorio.',
            'departamento_id.exists' => 'El departamento seleccionado no existe.',
            'user_id.required' => 'El líder del proceso es obligatorio.',
            'user_id.exists' => 'El usuario seleccionado no existe.',
            'nombre.required' => 'El nombre del documento es obligatorio.',
            'tipo_documento.required' => 'El tipo de documento es obligatorio.',
            'tipo_documento.exists' => 'El tipo de documento seleccionado no está registrado.',
            'codigo.required' => 'El código es obligatorio.',
            'codigo.unique' => 'Ya existe un documento con este código en el departamento.',
            'fecha_emision.required' => 'La fecha de emisión es obligatoria.',
            'fecha_emision.date' => 'La fecha de emisión no es válida.',
            'fecha_actualizacion.date' => 'La fecha de actualización no es válida.',
            'fecha_actualizacion.after_or_equal' => 'La fecha de actualización no puede ser anterior a la fecha de emisión.',
            'version.required' => 'La versión es obligatoria.',
            'version.integer' => 'La versión debe ser un número entero.',
            'version.min' => 'La versión debe ser al menos 1.',
            'medio_fisico.required_without' => 'Debes indicar el medio físico o el medio digital.',
            'medio_digital.required_without' => 'Debes indicar el medio físico o el medio digital.',
            'medio_digital.url' => 'El medio digital debe ser un enlace válido.',
        ];
    }
}
