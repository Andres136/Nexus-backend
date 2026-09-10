<?php

namespace App\Http\Requests\Hseq;

use App\Models\Hseq\AuditoriaPregunta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CalificarAuditoriaPreguntaRequest extends FormRequest
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
            'calificacion' => 'required|integer|between:1,5',
            'observaciones' => 'nullable|string',
            // Clasificación del hallazgo para el informe de auditoría.
            'tipo_hallazgo' => ['nullable', Rule::in(AuditoriaPregunta::TIPOS_HALLAZGO)],
        ];
    }

    public function messages()
    {
        return [
            'calificacion.required' => 'La calificación es obligatoria.',
            'calificacion.integer' => 'La calificación debe ser un número entero.',
            'calificacion.between' => 'La calificación debe estar entre 1 y 5 estrellas.',
            'observaciones.string' => 'Las observaciones deben ser una cadena de texto.',
            'tipo_hallazgo.in' => 'El tipo de hallazgo no es válido.',
        ];
    }
}
