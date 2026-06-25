<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class OrdenCompraProveedorRequest extends FormRequest
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
            'proveedor_id' => 'required|exists:proveedores,id',
            'observaciones' => 'nullable|string|max:1000',
            'bodega_id' => 'nullable|exists:bodegas,id',
            'sede_id' => 'nullable|exists:sedes,id',
            'fecha_entrega' => 'required|date',
            'empresa_id' => 'required|exists:empresas,id',
            'producto_id' => 'nullable|exists:products,id',
            'detalles.*.code'=>'nullable|string|max:100',
            'detalles.*.descripcion' => 'required|string|max:255',
           'detalles.*.cantidad_solicitada' => 'required|numeric|min:0.01',
          'detalles.*.cantidad_entregada' => 'nullable|numeric|gte:0',
            'detalles.*.origenes' => 'nullable|array',
            'detalles.*.origenes.*.orden_compra_id' => 'required_with:detalles.*.origenes|exists:orden__compras,id',
            'detalles.*.origenes.*.orden_compra_detalle_id' => 'required_with:detalles.*.origenes|exists:orden__compra__detalles,id',
            'detalles.*.origenes.*.producto_id' => 'nullable|exists:products,id',
            'detalles.*.origenes.*.sede_id' => 'nullable|exists:sedes,id',
            'detalles.*.origenes.*.bodega_id' => 'nullable|exists:bodegas,id',
            'detalles.*.origenes.*.cantidad_solicitada' => 'required_with:detalles.*.origenes|numeric|min:0.01',
            'detalles.*.origenes.*.cantidad_prioridad' => 'nullable|numeric|min:0',
            'detalles.*.origenes.*.cantidad_recibida_aplicada' => 'nullable|numeric|min:0',
            'detalles.*.origenes.*.prioridad_snapshot' => 'nullable|array',


        ];
    }

    public function messages(): array
    {
        return [
            'proveedor_id.required' => 'El campo proveedor es obligatorio.',
            'proveedor_id.exists' => 'El proveedor seleccionado no es válido.',
            'empresa_id.required' => 'El campo empresa es obligatorio.',
             'empresa_id.exists' => 'La empresa seleccionada no es válida.',
             'bodega_id.exists' => 'La bodega seleccionada no es válida.',
             'sede_id.exists' => 'La sede seleccionada no es válida.',
                'fecha_entrega.required' => 'El campo fecha de entrega es obligatorio.',
                'fecha_entrega.date' => 'El campo fecha de entrega debe ser una fecha válida.',
             'producto_id.exists' => 'El producto seleccionado no es válido.',
              'detalles.*.code.string' => 'El código del detalle debe ser una cadena de texto.',
            'detalles.*.descripcion.required' => 'La descripción del detalle es obligatoria.',
            'detalles.*.descripcion.string' => 'La descripción del detalle debe ser una cadena de texto.',
            'detalles.*.descripcion.max' => 'La descripción del detalle no puede tener más de 255 caracteres.',
            'detalles.*.cantidad_solicitada.required' => 'La cantidad solicitada es obligatoria.',
            'detalles.*.cantidad_solicitada.numeric' => 'La cantidad solicitada debe ser un número.',
            'detalles.*.cantidad_solicitada.min' => 'La cantidad solicitada debe ser al menos 0.01.',
            'detalles.*.cantidad_entregada.numeric' => 'La cantidad entregada debe ser un número.',
            'detalles.*.cantidad_entregada.min' => 'La cantidad entregada debe ser al menos 0.',
            
        ];
    }
}
