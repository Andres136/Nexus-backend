<?php

namespace App\Http\Requests\Hseq;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsumoServicioRequest extends FormRequest
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
            'sede_id' => 'required|exists:sedes,id',
            'tipo_servicio_id' => 'required|exists:tipo_servicios,id',
            'valor_factura' => 'nullable|numeric',
            'consumo' => 'required|numeric',
            'fecha_consumo' => 'required|date',
            'fecha_pago' => 'nullable|date',
           
            'consumo_percapita' => 'nullable|numeric',
        ];
    }

    public function messages()
    {
        return [
            'sede_id.required' => 'La sede es obligatoria.',
            'sede_id.exists' => 'La sede seleccionada no existe.',
            'tipo_servicio_id.required' => 'El tipo de servicio es obligatorio.',
            'tipo_servicio_id.exists' => 'El tipo de servicio seleccionado no existe.',
            'valor_factura.numeric' => 'El valor de la factura debe ser un número.',
            'consumo.required' => 'El consumo es obligatorio.',
            'consumo.numeric' => 'El consumo debe ser un número.',
            'fecha_consumo.required' => 'La fecha de consumo es obligatoria.',
            'fecha_consumo.date' => 'La fecha de consumo debe ser una fecha válida.',
            'fecha_pago.date' => 'La fecha de pago debe ser una fecha válida.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser "pendiente" o "pagado".',
            'consumo_percapita.numeric' => 'El consumo per cápita debe ser un número.',
        ];
    }
}
