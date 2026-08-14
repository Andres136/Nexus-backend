<?php

namespace App\Http\Requests\Hseq;

use Illuminate\Foundation\Http\FormRequest;

class StoreEficaciaEvaluacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'observacion' => 'required|string',
            'proxima_verificacion' => 'nullable|date',
            'calificaciones' => 'required|array|min:1',
            'calificaciones.*.hallazgo_id' => 'required|integer',
            'calificaciones.*.calificacion' => 'required|integer|min:1|max:5',
        ];
    }

    public function messages(): array
    {
        return [
            'observacion.required' => 'Agrega una observación de la verificación.',
            'calificaciones.required' => 'Califica al menos un hallazgo vinculado.',
            'calificaciones.*.calificacion.integer' => 'Calificación inválida.',
        ];
    }
}
