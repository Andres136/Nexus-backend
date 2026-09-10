<?php

namespace App\Http\Requests\Rutas;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarRecogidaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'detalles' => 'required|array|min:1',
            'detalles.*.orden_compra_proveedor_detalle_id' => 'required|exists:orden_compra_proveedor_detalles,id',
            'detalles.*.cantidad_recogida' => 'required|numeric|gt:0',
            'observaciones' => 'nullable|string|max:1000',
            'archivos' => 'required|array|min:1',
            'archivos.*' => 'required|image|max:5120',
            'generar_orden_servicio' => 'nullable|boolean',
            'empresa_id' => 'required_if:generar_orden_servicio,1|nullable|exists:empresas,id',
            'proveedor_destino_id' => 'required_if:generar_orden_servicio,1|nullable|exists:proveedores,id',
            'os_observaciones' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'detalles.required' => 'Debes registrar al menos una línea recogida.',
            'detalles.min' => 'Debes registrar al menos una línea recogida.',
            'detalles.*.orden_compra_proveedor_detalle_id.required' => 'Falta indicar la línea de la orden de compra.',
            'detalles.*.orden_compra_proveedor_detalle_id.exists' => 'La línea especificada no existe.',
            'detalles.*.cantidad_recogida.required' => 'La cantidad recogida es obligatoria.',
            'detalles.*.cantidad_recogida.numeric' => 'La cantidad recogida debe ser un número.',
            'detalles.*.cantidad_recogida.gt' => 'La cantidad recogida debe ser mayor que cero.',
            'archivos.required' => 'Debes adjuntar al menos una foto de evidencia.',
            'archivos.min' => 'Debes adjuntar al menos una foto de evidencia.',
            'archivos.*.required' => 'Debes adjuntar al menos una foto de evidencia.',
            'archivos.*.image' => 'Cada archivo debe ser una imagen.',
            'archivos.*.max' => 'Cada imagen no puede exceder 5MB.',
            'empresa_id.required_if' => 'Selecciona la empresa que solicita la orden de servicio.',
            'proveedor_destino_id.required_if' => 'Selecciona el proveedor que realizará el proceso.',
        ];
    }
}
