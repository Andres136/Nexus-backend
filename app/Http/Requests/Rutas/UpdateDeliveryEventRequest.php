<?php

namespace App\Http\Requests\Rutas;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => 'required|in:entrega,recogida',
            'orden_id' => 'required_if:tipo,entrega|nullable|exists:orden__compras,id',
            'proveedor_id' => 'required_if:tipo,recogida|nullable|exists:proveedores,id',
            'ordenes_compra_proveedor_ids' => 'nullable|array',
            'ordenes_compra_proveedor_ids.*' => 'exists:orden_compra_proveedores,id',
            'fecha_entrega' => 'required|date',
            'hora' => 'required|date_format:H:i',
            'usuario_id' => 'required|exists:users,id',
            'vehiculo_id' => 'required|exists:vehiculos,id',
            'cantidad' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string|max:255',
            'estado' => 'required|in:pendiente,completado,cancelado,en_ruta',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'El tipo de evento es obligatorio.',
            'tipo.in' => 'El tipo debe ser entrega o recogida.',
            'orden_id.required_if' => 'El ID de la orden es obligatorio para una entrega.',
            'orden_id.exists' => 'La orden especificada no existe.',
            'proveedor_id.required_if' => 'El proveedor es obligatorio para una recogida.',
            'proveedor_id.exists' => 'El proveedor especificado no existe.',
            'fecha_entrega.required' => 'La fecha de entrega es obligatoria.',
            'fecha_entrega.date' => 'La fecha de entrega debe ser una fecha válida.',
            'hora.required' => 'La hora de entrega es obligatoria.',
            'hora.date_format' => 'La hora de entrega debe tener el formato HH:MM.',
            'usuario_id.required' => 'El ID del usuario es obligatorio.',
            'usuario_id.exists' => 'El usuario especificado no existe.',
            'vehiculo_id.required' => 'El ID del vehículo es obligatorio.',
            'vehiculo_id.exists' => 'El vehículo especificado no existe.',
            'cantidad.numeric' => 'La cantidad debe ser un número.',
            'cantidad.min' => 'La cantidad no puede ser negativa.',
            'observaciones.string' => 'Las observaciones deben ser una cadena de texto.',
            'observaciones.max' => 'Las observaciones no pueden exceder los 255 caracteres.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser uno de los siguientes: pendiente, completado, cancelado, en_ruta.',
        ];
    }
}
