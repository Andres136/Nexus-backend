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
            'detalles.*.cantidad_recogida' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string|max:1000',
            'archivos.*' => 'nullable|image|max:5120',
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
            'archivos.*.image' => 'Cada archivo debe ser una imagen.',
            'archivos.*.max' => 'Cada imagen no puede exceder 5MB.',
        ];
    }
}
