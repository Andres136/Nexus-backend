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
    
            'detalles' => 'required|array',
            'detalles.*.orden_compra_detalle_id' => 'required|integer|exists:orden_compra_proveedor_detalles,id',
            'detalles.*.cantidad' => 'required|numeric|min:1',
        ];
    }

    public function messages()
    {
        return [
            'detalles.required' => 'Debes proporcionar al menos un detalle para la orden de servicio.',
            'detalles.array' => 'El campo detalles debe ser un arreglo.',
            'detalles.*.orden_compra_detalle_id.required' => 'El campo orden_compra_detalle_id es obligatorio en cada detalle.',
            'detalles.*.orden_compra_detalle_id.integer' => 'El campo orden_compra_detalle_id debe ser un número entero en cada detalle.',
            'detalles.*.orden_compra_detalle_id.exists' => 'El orden_compra_detalle_id proporcionado en cada detalle no existe.',
            'detalles.*.cantidad.required' => 'El campo cantidad es obligatorio en cada detalle.',
            'detalles.*.cantidad.numeric' => 'El campo cantidad debe ser un número en cada detalle.',
            'detalles.*.cantidad.min' => 'El campo cantidad debe ser al menos 1 en cada detalle.',
        ];
    }
}
