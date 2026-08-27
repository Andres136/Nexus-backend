<?php

namespace App\Http\Requests\Hseq;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuditoriaRequest extends FormRequest
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
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'hora' => 'nullable|date_format:H:i',
            'lugar' => 'nullable|string|max:255',
            'objetivo' => 'nullable|string',
            'alcance' => 'nullable|string',
            'observaciones' => 'nullable|string',
            // Participantes adicionales al equipo auditor; quien crea la auditoría se agrega
            // siempre como primer participante, esto es solo para sumar a alguien más.
            'participantes' => 'nullable|array',
            'participantes.*' => 'integer|exists:users,id',
        ];
    }

    public function messages()
    {
        return [
            'participantes.array' => 'Los participantes deben ser una lista.',
            'participantes.*.exists' => 'Uno de los participantes especificados no existe.',
            'fecha_inicio.required' => 'La fecha de inicio de la auditoría es obligatoria.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_fin.required' => 'La fecha de fin de la auditoría es obligatoria.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
            'hora.date_format' => 'La hora debe tener el formato HH:MM.',
            'observaciones.string' => 'Las observaciones deben ser una cadena de texto.',
        ];
    }
}
