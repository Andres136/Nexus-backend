<?php

namespace App\Http\Requests\contabilidad;

use Illuminate\Foundation\Http\FormRequest;

class StoreFacturaCompreRequest extends FormRequest
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

            // 🔹 FACTURA
            'factura' => 'required|array',
            'factura.proveedor_id' => 'required|exists:proveedores,id',
            'factura.fecha_emision' => 'required|date',
            'factura.fecha_vencimiento' => 'nullable|date',
            'factura.numero_factura_proveedor' => 'nullable|string|max:100',
            'factura.sede_id' => 'required|exists:sedes,id',

            // 🔹 DETALLES (PRODUCTOS)
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:products,id',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
            'detalles.*.bodega_id' => 'required|exists:bodegas,id',

            // 🔹 PAGOS
            'pagos' => 'nullable|array',
            'pagos.*.forma_pago_id' => 'required|exists:formas_pago,id',
            'pagos.*.monto' => 'required|numeric|min:0',

            // 🔹 GASTOS
            'gastos' => 'nullable|array',
            'gastos.*.nombre' => 'required|string|max:100',
            'gastos.*.monto' => 'required|numeric|min:0',

            // 🔹 IMPUESTOS
            'impuestos' => 'nullable|array',
            'impuestos.*.impuesto_id' => 'required|exists:impuestos,id',
            'impuestos.*.monto' => 'required|numeric|min:0',
        ];
    }

    public function withValidator($validator)
{
    $validator->after(function ($validator) {

        $pagos = collect($this->pagos ?? []);
        $totalPagos = $pagos->sum('monto');

        $detalles = collect($this->detalles ?? []);
$totalFactura = $detalles->sum(function ($d) {
    return $d['cantidad'] * $d['precio_unitario'];
});

$totalGastos = collect($this->gastos ?? [])->sum('monto');
$totalImpuestos = collect($this->impuestos ?? [])->sum('monto');

$totalReal = $totalFactura + $totalGastos + $totalImpuestos;

if ($pagos->count() > 0 && $totalPagos > $totalReal) {
    $validator->errors()->add('pagos', 'Los pagos no pueden superar el total de la factura');
}
    });
}


    public function messages()
    {
        return [
            'factura.proveedor_id.required' => 'El campo proveedor es obligatorio.',
            'factura.proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'factura.fecha_emision.required' => 'El campo fecha de emisión es obligatorio.',
            'factura.fecha_emision.date' => 'El campo fecha de emisión debe ser una fecha válida.',
            'factura.fecha_vencimiento.date' => 'El campo fecha de vencimiento debe ser una fecha válida.',
            'factura.numero_factura_proveedor.string' => 'El número de factura del proveedor debe ser una cadena de texto.',
            'factura.numero_factura_proveedor.max' => 'El número de factura del proveedor no puede exceder los 100 caracteres.',
            'factura.sede_id.required' => 'El campo sede es obligatorio.',
            'factura.sede_id.exists' => 'La sede seleccionada no existe.',

            'detalles.required' => 'Debe agregar al menos un detalle de producto.',
            'detalles.*.producto_id.required' => 'El campo producto es obligatorio en cada detalle.',
            'detalles.*.producto_id.exists' => 'El producto seleccionado en el detalle no existe.',
            'detalles.*.cantidad.required' => 'El campo cantidad es obligatorio en cada detalle.',
            'detalles.*.cantidad.numeric' => 'El campo cantidad debe ser un número.',
            'detalles.*.cantidad.min' => 'La cantidad debe ser al menos 0.01.',
            'detalles.*.precio_unitario.required' => 'El campo precio unitario es obligatorio en cada detalle.',
            'detalles.*.precio_unitario.numeric' => 'El campo precio unitario debe ser un número.',
            'detalles.*.precio_unitario.min' => 'El precio unitario no puede ser negativo.',
            'detalles.*.bodega_id.required' => 'El campo bodega es obligatorio en cada detalle.',
            'detalles.*.bodega_id.exists' => 'La bodega seleccionada en el detalle no existe.',

            // Mensajes para pagos, gastos e impuestos pueden ser añadidos aquí si se desea
            'pagos.*.forma_pago_id.required' => 'El campo forma de pago es obligatorio en cada pago.',
            'pagos.*.forma_pago_id.exists' => 'La forma de pago seleccionada en el pago no existe.',
            'pagos.*.monto.required' => 'El campo monto es obligatorio en cada pago.',
            'pagos.*.monto.numeric' => 'El campo monto debe ser un número.',
            'pagos.*.monto.min' => 'El monto no puede ser negativo.',

            'gastos.*.nombre.required' => 'El campo nombre es obligatorio en cada gasto.',
            'gastos.*.nombre.string' => 'El campo nombre debe ser una cadena de texto.',
            'gastos.*.nombre.max' => 'El campo nombre no puede exceder los 100 caracteres.',
            'gastos.*.monto.required' => 'El campo monto es obligatorio en cada gasto.',
            'gastos.*.monto.numeric' => 'El campo monto debe ser un número.',
            'gastos.*.monto.min' => 'El monto no puede ser negativo.',

            'impuestos.*.impuesto_id.required' => 'El campo impuesto es obligatorio en cada impuesto.',
            'impuestos.*.impuesto_id.exists' => 'El impuesto seleccionado en el impuesto no existe.',
            'impuestos.*.monto.required' => 'El campo monto es obligatorio en cada impuesto.',
            'impuestos.*.monto.numeric' => 'El campo monto debe ser un número.',
            'impuestos.*.monto.min' => 'El monto no puede ser negativo.',

        ];
    }       
}
