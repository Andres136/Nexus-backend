<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CapacitacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:5000',
            'fecha_realizacion' => 'required|date|after_or_equal:today',
            'hora_inicio' => 'nullable|date_format:H:i',
            'hora_fin' => 'nullable|date_format:H:i|after:hora_inicio',
            'lugar' => 'nullable|string|max:255',
            'modalidad' => 'nullable|in:presencial,virtual,mixta',
            'estado' => 'nullable|in:programada,realizada,cancelada',
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'El título de la capacitación es obligatorio.',
            'fecha_realizacion.required' => 'La fecha de realización es obligatoria.',
            'fecha_realizacion.after_or_equal' => 'No se puede programar una capacitación en una fecha que ya pasó.',
            'hora_fin.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'modalidad.in' => 'La modalidad debe ser presencial, virtual o mixta.',
            'estado.in' => 'El estado debe ser programada, realizada o cancelada.',
        ];
    }
}
