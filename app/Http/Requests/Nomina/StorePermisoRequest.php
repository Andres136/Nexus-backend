<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StorePermisoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $user = $this->user();

        if ($user) {
            $this->merge(['user_id' => $user->id]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'       => 'nullable|integer|exists:users,id',
            'fecha'         => 'required|date',
            'tipo'          => 'required|in:llegada_tarde,salida_temprana,ausencia_parcial',
            'hora_inicio'   => 'required|date_format:H:i',
            'hora_fin'      => 'required|date_format:H:i|after:hora_inicio',
            'motivo'        => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.exists'         => 'El empleado no existe.',
            'fecha.required'         => 'La fecha es obligatoria.',
            'tipo.required'          => 'El tipo de permiso es obligatorio.',
            'tipo.in'                => 'El tipo debe ser: llegada_tarde, salida_temprana o ausencia_parcial.',
            'hora_inicio.required'   => 'La hora de inicio es obligatoria.',
            'hora_inicio.date_format' => 'La hora de inicio debe tener formato HH:MM.',
            'hora_fin.required'      => 'La hora de fin es obligatoria.',
            'hora_fin.date_format'   => 'La hora de fin debe tener formato HH:MM.',
            'hora_fin.after'         => 'La hora de fin debe ser posterior a la de inicio.',
            'motivo.required'        => 'El motivo es obligatorio.',
            'motivo.string'          => 'El motivo debe ser una cadena de texto.',
            'motivo.max'             => 'El motivo no debe exceder los 255 caracteres.',
        ];
    }
}
