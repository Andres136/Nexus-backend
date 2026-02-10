<?php

namespace App\Http\Requests\Crm;

use App\Models\Crm\Inventario;
use Illuminate\Foundation\Http\FormRequest;

class StockMasivoRequest extends FormRequest
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
        'items' => ['required', 'array', 'min:1'],

        'items.*.producto_id' => ['required', 'integer', 'exists:products,id'],
        'items.*.cantidad' => ['required', 'numeric', 'min:0'],
        'items.*.orden_trabajo_id' => ['nullable', 'integer'],
        'items.*.orden_compra_id' => ['nullable', 'integer'],

        // 🔹 Ya no es obligatoria si existen equivalentes
        'items.*.bodegas' => ['nullable', 'array'],
        //'items.*.bodegas' => ['required_with:items.*.producto_equivalentes', 'array'],
        'items.*.bodegas.*.bodega_id' => ['required_with:items.*.bodegas', 'integer', 'exists:bodegas,id'],
        'items.*.bodegas.*.cantidad' => ['required_with:items.*.bodegas', 'numeric', 'min:0'],

        'items.*.producto_equivalentes' => ['nullable', 'array'],
        'items.*.producto_equivalentes.*.id' => ['required_with:items.*.producto_equivalentes', 'integer', 'exists:products,id'],
        'items.*.producto_equivalentes.*.razon' => ['nullable', 'string'],
       // 'items.*.producto_equivalentes.*.bodegas' => ['required_with:items.*.producto_equivalentes', 'array'],
        'items.*.producto_equivalentes.*.bodegas.*.bodega_id' => ['required_with:items.*.producto_equivalentes.*.bodegas', 'integer', 'exists:bodegas,id'],
        'items.*.producto_equivalentes.*.bodegas.*.cantidad' => ['required_with:items.*.producto_equivalentes.*.bodegas', 'numeric', 'min:0'],

        // 🔸 Validación final: asegurar que haya alguna cantidad > 0
        'validacion_global' => [
            function ($attribute, $value, $fail) {
                foreach ($this->input('items', []) as $item) {
                    $totalBodegas = collect($item['bodegas'] ?? [])->sum('cantidad');
                    $totalEquivalentes = collect($item['producto_equivalentes'] ?? [])
                        ->flatMap(fn($eq) => $eq['bodegas'] ?? [])
                        ->sum('cantidad');

                    if (($totalBodegas + $totalEquivalentes) <= 0) {
                        $fail("El producto #{$item['producto_id']} no tiene cantidades válidas para descontar.");
                    }
                }
            },
        ],
    ];
}
public function withValidator($validator)
{
    $validator->after(function ($validator) {

        foreach ($this->input('items', []) as $i => $item) {

            $totalBodegas = collect($item['bodegas'] ?? [])
                ->sum(fn ($b) => (float) ($b['cantidad'] ?? 0));

            $totalEquivalentes = collect($item['producto_equivalentes'] ?? [])
                ->flatMap(fn ($eq) => $eq['bodegas'] ?? [])
                ->sum(fn ($b) => (float) ($b['cantidad'] ?? 0));

            $cantidadReal = $totalBodegas + $totalEquivalentes;

            // ✅ SI NO PARTICIPA EN ESTA ENTREGA → NO VALIDAR
            if ($cantidadReal <= 0) {
                continue;
            }

            // ❌ Tiene cantidad pero no tiene origen
            if ($totalBodegas <= 0 && $totalEquivalentes <= 0) {
                $validator->errors()->add(
                    "items.$i.bodegas",
                    "Debe especificar al menos una bodega o un equivalente para este ítem."
                );
            }

            // 🔹 Validar stock SOLO de equivalentes usados
            foreach ($item['producto_equivalentes'] ?? [] as $e => $equivalente) {
                foreach ($equivalente['bodegas'] ?? [] as $b => $bodega) {

                    $cantidad = (float) ($bodega['cantidad'] ?? 0);
                    if ($cantidad <= 0) continue;

                    $stock = Inventario::where('producto_id', $equivalente['id'])
                        ->where('bodega_id', $bodega['bodega_id'])
                        ->sum('stock');

                    if ($cantidad > $stock) {
                        $validator->errors()->add(
                            "items.$i.producto_equivalentes.$e.bodegas.$b.cantidad",
                            "Stock insuficiente: Disponible $stock, Requerido $cantidad"
                        );
                    }
                }
            }
        }
    });
}


    public function messages(): array
    {
        return [
            'items.required' => 'Debe proporcionar al menos un ítem para procesar el stock masivo.',
            'items.array' => 'El campo de ítems debe ser un arreglo válido.',
            'items.min' => 'Debe haber al menos un ítem en la solicitud.',
            'items.*.producto_id.required' => 'El ID del producto es obligatorio para cada ítem.',
            'items.*.producto_id.integer' => 'El ID del producto debe ser un número entero válido.',
            'items.*.producto_id.exists' => 'El producto seleccionado no existe en el sistema.',
            'items.*.cantidad.required' => 'La cantidad es obligatoria para cada ítem.',
            'items.*.cantidad.numeric' => 'La cantidad debe ser un valor numérico válido.',
            'items.*.cantidad.min' => 'La cantidad debe ser al menos 1.',
            'items.*.bodegas.required_without' => 'Debe especificar bodegas o equivalentes para cada ítem.',
            'items.*.bodegas.array' => 'El campo de bodegas debe ser un arreglo válido.',
            'items.*.bodegas.*.bodega_id.required_with' => 'El ID de la bodega es obligatorio cuando se proporcionan bodegas.',
            'items.*.bodegas.*.bodega_id.integer' => 'El ID de la bodega debe ser un número entero válido.',
            'items.*.bodegas.*.bodega_id.exists' => 'La bodega seleccionada no existe en el sistema.',
            'items.*.bodegas.*.cantidad.required_with' => 'La cantidad en bodega es obligatoria cuando se proporcionan bodegas.',
            'items.*.bodegas.*.cantidad.numeric' => 'La cantidad en bodega debe ser un valor numérico válido.',
            'items.*.bodegas.*.cantidad.min' => 'La cantidad en bodega no puede ser negativa.',

        ];
    }
}
