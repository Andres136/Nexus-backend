<?php

namespace App\Http\Requests\Hseq;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductoNoConformeRequest extends FormRequest
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
            'origen' => 'required|in:cliente,proveedor,interno',
            'cliente_id' => 'required_if:origen,cliente|nullable|exists:clientes,id',
            'orden_compra_id' => 'required_if:origen,cliente|nullable|exists:orden__compras,id',
            'proveedor_id' => 'required_if:origen,proveedor|nullable|exists:proveedores,id',
            'orden_compra_proveedor_id' => 'required_if:origen,proveedor|nullable|exists:orden_compra_proveedores,id',
            'productos' => 'required_unless:origen,interno|nullable|array|min:1',
            'productos.*.producto_id' => 'required|exists:products,id',
            'productos.*.cantidad_afectada' => 'required|integer|min:1',
            'fecha_reporte' => 'required|date',
            'descripcion_inicial' => 'required|string',
            'tipo_falla' => 'required|string',
            'estado_id' => 'required|exists:estados,id'
        ];
    }

    public function messages()
    {
        return [
            'origen.required' => 'El origen del no conforme es obligatorio.',
            'origen.in' => 'El origen debe ser cliente, proveedor o interno.',
            'cliente_id.required_if' => 'El cliente es obligatorio cuando el origen es cliente.',
            'cliente_id.exists' => 'El cliente especificado no existe.',
            'orden_compra_id.required_if' => 'La orden de compra del cliente es obligatoria cuando el origen es cliente.',
            'orden_compra_id.exists' => 'La orden de compra especificada no existe.',
            'proveedor_id.required_if' => 'El proveedor es obligatorio cuando el origen es proveedor.',
            'proveedor_id.exists' => 'El proveedor especificado no existe.',
            'orden_compra_proveedor_id.required_if' => 'La orden de compra del proveedor es obligatoria cuando el origen es proveedor.',
            'orden_compra_proveedor_id.exists' => 'La orden de compra de proveedor especificada no existe.',
            'productos.required_unless' => 'Debes agregar al menos un producto.',
            'productos.min' => 'Debes agregar al menos un producto.',
            'productos.*.producto_id.required' => 'El producto es obligatorio.',
            'productos.*.producto_id.exists' => 'El producto especificado no existe.',
            'productos.*.cantidad_afectada.required' => 'La cantidad afectada es obligatoria.',
            'productos.*.cantidad_afectada.integer' => 'La cantidad afectada debe ser un número entero.',
            'productos.*.cantidad_afectada.min' => 'La cantidad afectada debe ser al menos 1.',
            'fecha_reporte.required' => 'La fecha de reporte es obligatoria.',
            'fecha_reporte.date' => 'La fecha de reporte debe ser una fecha válida.',
            'descripcion_inicial.required' => 'La descripción inicial es obligatoria.',
            'descripcion_inicial.string' => 'La descripción inicial debe ser una cadena de texto.',
            'tipo_falla.required' => 'El tipo de falla es obligatorio.',
            'tipo_falla.string' => 'El tipo de falla debe ser una cadena de texto.',
            'estado_id.required' => 'El ID del estado es obligatorio.',
            'estado_id.exists' => 'El estado especificado no existe.'
        ];
    }
}
