<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StorePermisoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'       => 'required|integer|exists:users,id',
            'fecha'         => 'required|date',
            'tipo'          => 'required|in:llegada_tarde,salida_temprana,ausencia_parcial',
            'hora_inicio'   => 'required|date_format:H:i',
            'hora_fin'      => 'required|date_format:H:i|after:hora_inicio',
            'es_remunerado' => 'required|boolean',
            'motivo'        => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required'       => 'El empleado es obligatorio.',
            'user_id.exists'         => 'El empleado no existe.',
            'fecha.required'         => 'La fecha es obligatoria.',
            'tipo.required'          => 'El tipo de permiso es obligatorio.',
            'tipo.in'                => 'El tipo debe ser: llegada_tarde, salida_temprana o ausencia_parcial.',
            'hora_inicio.required'   => 'La hora de inicio es obligatoria.',
            'hora_inicio.date_format' => 'La hora de inicio debe tener formato HH:MM.',
            'hora_fin.required'      => 'La hora de fin es obligatoria.',
            'hora_fin.date_format'   => 'La hora de fin debe tener formato HH:MM.',
            'hora_fin.after'         => 'La hora de fin debe ser posterior a la de inicio.',
            'es_remunerado.required' => 'Debe indicar si el permiso es remunerado o no.',
        ];
    }
}
