<?php

namespace App\Http\Requests\Rutas;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliverRecordRequest extends FormRequest
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
            'delivery_event_id' => 'required|exists:delivery_events,id',
            'fecha_real' => 'required|date',
            'hora_real' => 'required|date_format:H:i',
            'cantidad_entregada' => 'required|numeric|min:0',
            'resultado' => 'required|in:entregado,no_entregado,parcial',
            'motivo_no_entrega' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string|max:255',
            'usuario_id' => 'required|exists:users,id',
        ];
    }


    public function messages(): array
    {
        return [
            'delivery_event_id.required' => 'El ID del evento de entrega es obligatorio.',
            'delivery_event_id.exists' => 'El evento de entrega especificado no existe.',
            'fecha_real.required' => 'La fecha real de entrega es obligatoria.',
            'fecha_real.date' => 'La fecha real de entrega debe ser una fecha válida.',
            'hora_real.required' => 'La hora real de entrega es obligatoria.',
            'hora_real.date_format' => 'La hora real de entrega debe tener el formato HH:MM.',
            'cantidad_entregada.required' => 'La cantidad entregada es obligatoria.',
            'cantidad_entregada.numeric' => 'La cantidad entregada debe ser un número.',
            'cantidad_entregada.min' => 'La cantidad entregada no puede ser negativa.',
            'resultado.required' => 'El resultado de la entrega es obligatorio.',
            'resultado.in' => 'El resultado debe ser uno de los siguientes: entregado, no_entregado, parcial.',
            'motivo_no_entrega.string' => 'El motivo de no entrega debe ser una cadena de texto.',
            'motivo_no_entrega.max' => 'El motivo de no entrega no puede exceder los 255 caracteres.',
            'observaciones.string' => 'Las observaciones deben ser una cadena de texto.',
            'observaciones.max' => 'Las observaciones no pueden exceder los 255 caracteres.',
            'usuario_id.required' => 'El ID del usuario es obligatorio.',
            'usuario_id.exists' => 'El usuario especificado no existe.',
        ];
    }
}
