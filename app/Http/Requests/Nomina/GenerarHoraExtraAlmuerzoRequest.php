<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class GenerarHoraExtraAlmuerzoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'                       => 'required|integer|exists:users,id',
            'sesiones'                      => 'required|array|min:1',
            'sesiones.*.work_session_uuid'  => 'required|string|exists:work_sessions,uuid',
            'sesiones.*.minutos'            => 'required|integer|min:30|max:480',
            'tipo'                          => 'required|in:diurna,nocturna,festiva,nocturna_festiva',
            'motivo'                        => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required'  => 'Debes seleccionar un empleado.',
            'sesiones.required' => 'Debes seleccionar al menos un día.',
            'sesiones.min'      => 'Debes seleccionar al menos un día.',
            'sesiones.*.minutos.min' => 'El mínimo por día es de 30 minutos.',
            'tipo.required'     => 'El tipo de hora extra es obligatorio.',
        ];
    }
}
