<?php

namespace App\Http\Requests\Compras;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequerimientoCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bodega_id' => 'nullable|integer|exists:bodegas,id',
            'orden_trabajo_id' => 'nullable|integer|exists:orden_de_trabajos,id',
            'prioridad' => 'nullable|in:baja,normal,urgente',
            'fecha_requerida' => 'nullable|date',
            'observacion' => 'nullable|string|max:2000',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'nullable|integer|exists:products,id',
            'detalles.*.cantidad_solicitada' => 'required|numeric|min:0.01',
            'detalles.*.cantidad_aprobada' => 'nullable|numeric|min:0',
            'detalles.*.costo_estimado' => 'nullable|numeric|min:0',
            'detalles.*.proveedor_sugerido_id' => 'nullable|integer|exists:proveedores,id',
            'detalles.*.referencia_sugerida' => 'nullable|string|max:255',
            'detalles.*.observacion' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'detalles.required' => 'Agrega al menos un producto al requerimiento.',
            'detalles.*.cantidad_solicitada.required' => 'La cantidad solicitada es obligatoria.',
            'detalles.*.cantidad_solicitada.min' => 'La cantidad debe ser mayor a cero.',
        ];
    }
}
