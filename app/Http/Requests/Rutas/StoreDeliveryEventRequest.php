<?php

namespace App\Http\Requests\Rutas;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryEventRequest extends FormRequest
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
            'orden_id' => 'required|exists:ordenes_trabajo,id',
            'fecha_entrega' => 'required|date',
            'hora' => 'required|date_format:H:i',
            'usuario_id' => 'required|exists:users,id',
            'vehiculo_id' => 'required|exists:vehiculos,id',
            'cantidad' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string|max:255',
            'estado' => 'required|in:pendiente,completado,cancelado,en_ruta',
        ];
    }


    public function messages(): array
    {
        return [
            'orden_id.required' => 'El ID de la orden es obligatorio.',
            'orden_id.exists' => 'La orden especificada no existe.',
            'fecha_entrega.required' => 'La fecha de entrega es obligatoria.',
            'fecha_entrega.date' => 'La fecha de entrega debe ser una fecha válida.',
            'hora.required' => 'La hora de entrega es obligatoria.',
            'hora.date_format' => 'La hora de entrega debe tener el formato HH:MM.',
            'usuario_id.required' => 'El ID del usuario es obligatorio.',
            'usuario_id.exists' => 'El usuario especificado no existe.',
            'vehiculo_id.required' => 'El ID del vehículo es obligatorio.',
            'vehiculo_id.exists' => 'El vehículo especificado no existe.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.numeric' => 'La cantidad debe ser un número.',
            'cantidad.min' => 'La cantidad no puede ser negativa.',
            'observaciones.string' => 'Las observaciones deben ser una cadena de texto.',
            'observaciones.max' => 'Las observaciones no pueden exceder los 255 caracteres.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser uno de los siguientes: pendiente, completado, cancelado, en_ruta.',
        ];
    }
}
