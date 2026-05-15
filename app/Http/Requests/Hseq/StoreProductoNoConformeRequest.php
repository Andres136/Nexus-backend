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
            'cliente_id' => 'required|exists:clientes,id',
            'producto_id' => 'required|exists:products,id',
            'orden_compra_id' => 'required|exists:orden__compras,id',
            'fecha_reporte' => 'required|date',
            'cantidad_afectada' => 'required|integer',
            'descripcion_inicial' => 'required|string',
            'tipo_falla' => 'required|string',
            'estado_id' => 'required|exists:estados,id'
        ];
    }

    public function messages()
    {
        return [
            'cliente_id.required' => 'El ID del cliente es obligatorio.',
            'cliente_id.exists' => 'El cliente especificado no existe.',
            'producto_id.required' => 'El  producto es obligatorio.',
            'producto_id.exists' => 'El producto especificado no existe.',
            'orden_compra_id.required' => 'La orden de compra es obligatoria.',
            'orden_compra_id.exists' => 'La orden de compra especificada no existe.',
            'fecha_reporte.required' => 'La fecha de reporte es obligatoria.',
            'fecha_reporte.date' => 'La fecha de reporte debe ser una fecha válida.',
            'cantidad_afectada.required' => 'La cantidad afectada es obligatoria.',
            'cantidad_afectada.integer' => 'La cantidad afectada debe ser un número entero.',
            'descripcion_inicial.required' => 'La descripción inicial es obligatoria.',
            'descripcion_inicial.string' => 'La descripción inicial debe ser una cadena de texto.',
            'tipo_falla.required' => 'El tipo de falla es obligatorio.',
            'tipo_falla.string' => 'El tipo de falla debe ser una cadena de texto.',
            'estado_id.required' => 'El ID del estado es obligatorio.',
            'estado_id.exists' => 'El estado especificado no existe.'
        ];
    }
}
