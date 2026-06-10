<?php

namespace App\Http\Requests\contabilidad;

use App\Models\contabilidad\Impuesto;
use App\Models\Crm\OrdenCompraProveedor;
use App\Models\Crm\OrdenCompraProveedorDetalle;
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
            'ordenes_compra_proveedor_ids' => 'nullable|array',
            'ordenes_compra_proveedor_ids.*' => 'distinct|exists:orden_compra_proveedores,id',
            'factura.empresa_id' => 'required|exists:empresas,id',
            'factura.fecha_emision' => 'required|date',
            'factura.fecha_vencimiento' => 'nullable|date',
            'factura.numero_factura_proveedor' => 'required|string|max:100',
            'factura.sede_id' => 'required|exists:sedes,id',
            'factura.forma_pago_id' => 'required|exists:formas_pago,id',

            // 🔹 DETALLES (PRODUCTOS)
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:products,id',
            'detalles.*.orden_compra_proveedor_detalle_id' => 'nullable|exists:orden_compra_proveedor_detalles,id',
            'detalles.*.puck_id' => 'required|exists:puck,id',

            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.precio_unitario' => 'required|numeric|min:0.01',
            'detalles.*.bodega_id' => 'nullable|exists:bodegas,id',
            'detalles.*.impuestos' => 'nullable|array',
'detalles.*.impuestos.*.impuesto_id' => 'required|exists:impuestos,id',

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
            'impuestos' => 'nullable|array',
'impuestos.*.impuesto_id' => 'required|exists:impuestos,id',
        ];
    }

    public function withValidator($validator)
{
    $validator->after(function ($validator) {
        $ordenIds = collect($this->input('ordenes_compra_proveedor_ids', []));
        $proveedorId = $this->input('factura.proveedor_id');

        if ($ordenIds->isNotEmpty() && $proveedorId) {
            $ordenesValidas = OrdenCompraProveedor::whereIn('id', $ordenIds)
                ->where('proveedor_id', $proveedorId)
                ->count();

            if ($ordenesValidas !== $ordenIds->unique()->count()) {
                $validator->errors()->add(
                    'ordenes_compra_proveedor_ids',
                    'Todas las órdenes de compra deben pertenecer al proveedor seleccionado.'
                );
            }
        }

        foreach ($this->input('detalles', []) as $index => $detalle) {
            $ordenDetalleId = $detalle['orden_compra_proveedor_detalle_id'] ?? null;
            if (!$ordenDetalleId) continue;

            $detalleValido = OrdenCompraProveedorDetalle::whereKey($ordenDetalleId)
                ->where('producto_id', $detalle['producto_id'] ?? null)
                ->whereIn('orden_id', $ordenIds)
                ->exists();

            if (!$detalleValido) {
                $validator->errors()->add(
                    "detalles.{$index}.orden_compra_proveedor_detalle_id",
                    'El detalle no pertenece a una de las órdenes seleccionadas.'
                );
            }
        }

        $pagos = collect($this->pagos ?? []);
        $totalPagos = $pagos->sum('monto');

        $detalles = collect($this->detalles ?? []);
$totalFactura = $detalles->sum(function ($d) {
    return $d['cantidad'] * $d['precio_unitario'];
});

$totalGastos = collect($this->gastos ?? [])->sum('monto');
$totalImpuestos = 0;

// 🔹 impuestos por detalle
foreach ($this->detalles ?? [] as $detalle) {

    $base = $detalle['cantidad'] * $detalle['precio_unitario'];

    if (!empty($detalle['impuestos'])) {
        foreach ($detalle['impuestos'] as $imp) {

            $impuesto = Impuesto::find($imp['impuesto_id']);
            if (!$impuesto) continue;

            $totalImpuestos += $base * ($impuesto->porcentaje / 100);
        }
    }
}

// 🔹 impuesto general
foreach ($this->impuestos ?? [] as $imp) {

    $impuesto = Impuesto::find($imp['impuesto_id']);
    if (!$impuesto) continue;

    $totalImpuestos += $totalFactura * ($impuesto->porcentaje / 100);
}

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
            'ordenes_compra_proveedor_ids.*.exists' => 'Una de las órdenes de compra seleccionadas no existe.',
            'factura.empresa_id.required' => 'El campo empresa es obligatorio.',
            'factura.empresa_id.exists' => 'La empresa seleccionada no existe.',
            'factura.fecha_emision.required' => 'El campo fecha de emisión es obligatorio.',
            'factura.fecha_emision.date' => 'El campo fecha de emisión debe ser una fecha válida.',
            'factura.fecha_vencimiento.date' => 'El campo fecha de vencimiento debe ser una fecha válida.',
            'factura.numero_factura_proveedor.required' => 'El campo número de factura del proveedor es obligatorio.',
            'factura.numero_factura_proveedor.string' => 'El número de factura del proveedor debe ser una cadena de texto.',
            'factura.numero_factura_proveedor.max' => 'El número de factura del proveedor no puede exceder los 100 caracteres.',
            'factura.sede_id.required' => 'El campo sede es obligatorio.',
            'factura.sede_id.exists' => 'La sede seleccionada no existe.',
            'factura.forma_pago_id.required' => 'La forma de pago es obligatoria.',
'factura.forma_pago_id.exists' => 'La forma de pago seleccionada no existe.',

            'detalles.required' => 'Debe agregar al menos un detalle de producto.',
            'detalles.*.producto_id.required' => 'El campo producto es obligatorio en cada detalle.',
            'detalles.*.producto_id.exists' => 'El producto seleccionado en el detalle no existe.',
                'detalles.*.puck_id.exists' => 'El puck seleccionado en el detalle no existe.',
                'detalles.*.puck_id.required' => 'El campo puck es obligatorio en cada detalle.',
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
