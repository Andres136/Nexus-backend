<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHoraExtraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sede_id'          => 'nullable|integer|exists:sedes,id',
            'kiosko_device_id' => 'nullable|integer|exists:kiosko_devices,id',
            'fecha'            => 'required|date',
            'hora_inicio'      => 'required|date_format:H:i',
            'hora_fin'         => 'required|date_format:H:i|different:hora_inicio',
            'tipo'             => 'required|in:diurna,nocturna,festiva,nocturna_festiva',
            'motivo'           => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha debe ser una fecha válida.',
            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_inicio.date_format' => 'La hora de inicio debe tener formato HH:MM.',
            'hora_fin.required' => 'La hora final es obligatoria.',
            'hora_fin.date_format' => 'La hora final debe tener formato HH:MM.',
            'hora_fin.different' => 'La hora final debe ser diferente de la hora inicial.',
            'tipo.required' => 'El tipo de hora extra es obligatorio.',
            'tipo.in' => 'El tipo de hora extra no es válido.',
            'motivo.required' => 'Debes registrar un motivo.',
        ];
    }
}
