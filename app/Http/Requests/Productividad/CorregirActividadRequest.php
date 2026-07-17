<?php

namespace App\Http\Requests\Productividad;

use App\Models\Productividad\ActividadOperativa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CorregirActividadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => 'required|string|min:5|max:255',
            'observaciones' => 'nullable|string|max:2000',
            'datos_nuevos' => 'required|array|min:1',
            'datos_nuevos.estado' => ['nullable', Rule::in([
                ActividadOperativa::ESTADO_ACTIVA,
                ActividadOperativa::ESTADO_PAUSADA,
                ActividadOperativa::ESTADO_COMPLETADA,
                ActividadOperativa::ESTADO_CANCELADA,
                ActividadOperativa::ESTADO_BLOQUEADA,
                ActividadOperativa::ESTADO_INTERRUMPIDA,
            ])],
            'datos_nuevos.segundos' => 'nullable|integer|min:0',
            'datos_nuevos.titulo' => 'nullable|string|max:255',
            'datos_nuevos.descripcion' => 'nullable|string|max:2000',
            'datos_nuevos.resultado' => 'nullable|string|max:2000',
            'datos_nuevos.motivo_bloqueo' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'Debes indicar el motivo de la corrección.',
            'motivo.min' => 'El motivo debe tener al menos 5 caracteres.',
            'datos_nuevos.required' => 'Debes indicar al menos un dato para corregir.',
            'datos_nuevos.min' => 'Debes indicar al menos un dato para corregir.',
        ];
    }
}
