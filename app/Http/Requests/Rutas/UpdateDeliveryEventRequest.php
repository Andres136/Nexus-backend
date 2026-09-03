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
            'es_transportadora' => 'nullable|boolean',
            'usuario_id' => 'required_unless:es_transportadora,true|nullable|exists:users,id',
            'vehiculo_id' => 'required_unless:es_transportadora,true|nullable|exists:vehiculos,id',
            'transportadora_guia' => 'required_if:es_transportadora,true|nullable|string|max:100',
            'transportadora_nombre' => 'required_if:es_transportadora,true|nullable|string|max:150',
            'transportadora_cedula' => 'required_if:es_transportadora,true|nullable|string|max:30',
            'transportadora_placa' => 'required_if:es_transportadora,true|nullable|string|max:20',
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
            'usuario_id.required_unless' => 'El usuario responsable es obligatorio si no se envía por transportadora.',
            'usuario_id.exists' => 'El usuario especificado no existe.',
            'vehiculo_id.required_unless' => 'El vehículo es obligatorio si no se envía por transportadora.',
            'vehiculo_id.exists' => 'El vehículo especificado no existe.',
            'transportadora_guia.required_if' => 'La guía es obligatoria para un envío por transportadora.',
            'transportadora_nombre.required_if' => 'El nombre de quien recibe es obligatorio para un envío por transportadora.',
            'transportadora_cedula.required_if' => 'La cédula es obligatoria para un envío por transportadora.',
            'transportadora_placa.required_if' => 'La placa es obligatoria para un envío por transportadora.',
            'cantidad.numeric' => 'La cantidad debe ser un número.',
            'cantidad.min' => 'La cantidad no puede ser negativa.',
            'observaciones.string' => 'Las observaciones deben ser una cadena de texto.',
            'observaciones.max' => 'Las observaciones no pueden exceder los 255 caracteres.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser uno de los siguientes: pendiente, completado, cancelado, en_ruta.',
        ];
    }
}
