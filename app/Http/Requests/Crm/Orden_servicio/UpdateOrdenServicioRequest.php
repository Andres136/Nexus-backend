<?php

namespace App\Http\Requests\Crm\Orden_servicio;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrdenServicioRequest extends FormRequest
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
        'empresa_id' => 'required|exists:empresas,id',
        'proveedor_id' => 'required|exists:proveedores,id',
        'fecha' => 'required|date',
        'observaciones' => 'nullable|string',

        'detalles' => 'required|array',
        'detalles.*.orden_compra_detalle_id' => 'required|exists:orden_compra_proveedor_detalles,id',
        'detalles.*.cantidad' => 'required|numeric|min:1',
        'detalles.*.proceso_bolsas_id' => 'required|exists:proceso_bolsas,id',
        'detalles.*.observacion_id' => 'nullable|exists:orden_detalle_observaciones,id',
    ];
}
 
    public function messages()
    {
        return [
            'empresa_id.required' => 'El campo empresa_id es obligatorio.',
            'empresa_id.exists' => 'El empresa_id proporcionado no existe.',
            'proveedor_id.required' => 'El campo proveedor_id es obligatorio.',
            'proveedor_id.exists' => 'El proveedor_id proporcionado no existe.',
            'fecha.required' => 'El campo fecha es obligatorio.',
            'fecha.date' => 'El campo fecha debe ser una fecha válida.',
            'observaciones.string' => 'El campo observaciones debe ser una cadena de texto.',
            'detalles.required' => 'Debes proporcionar al menos un detalle para la orden de servicio.',
            'detalles.array' => 'El campo detalles debe ser un arreglo.',
            'detalles.*.orden_compra_detalle_id.required' => 'El campo orden_compra_detalle_id es obligatorio en cada detalle.',
            'detalles.*.orden_compra_detalle_id.exists' => 'El orden_compra_detalle_id proporcionado en cada detalle no existe.',
            'detalles.*.cantidad.required' => 'El campo cantidad es obligatorio en cada detalle.',
            'detalles.*.cantidad.numeric' => 'El campo cantidad debe ser un número en cada detalle.',
            'detalles.*.cantidad.min' => 'El campo cantidad debe ser al menos 1 en cada detalle.',
            'detalles.*.proceso_bolsas_id.required' => 'El campo proceso_bolsas_id es obligatorio en cada detalle.',
            'detalles.*.proceso_bolsas_id.exists' => 'El proceso_bolsas_id proporcionado en cada detalle no existe.',
            'detalles.*.observacion_id.exists' => 'El observacion_id proporcionado en cada detalle no existe.',
        ];
    }
}
