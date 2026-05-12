<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StoreHoraExtraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Lista de empleados a los que aplica el registro
            'users'          => 'required|array|min:1',
            'users.*'        => 'required|integer|exists:users,id',

            'fecha'          => 'required|date',
            'horas'          => 'required|numeric|min:0.5|max:24',
            'tipo'           => 'required|in:diurna,nocturna,festiva,nocturna_festiva',
            'motivo'         => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'users.required'    => 'Debe seleccionar al menos un empleado.',
            'users.array'       => 'El campo empleados debe ser una lista.',
            'users.min'         => 'Debe seleccionar al menos un empleado.',
            'users.*.exists'    => 'Uno o más empleados no existen.',
            'fecha.required'    => 'La fecha es obligatoria.',
            'fecha.date'        => 'La fecha debe ser una fecha válida.',
            'horas.required'    => 'Las horas son obligatorias.',
            'horas.min'         => 'El mínimo es 0.5 horas.',
            'horas.max'         => 'No puede registrar más de 24 horas en un día.',
            'tipo.required'     => 'El tipo de hora extra es obligatorio.',
            'tipo.in'           => 'El tipo debe ser: diurna, nocturna, festiva o nocturna_festiva.',
        ];
    }
}
