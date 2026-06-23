<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class StoreObservacionProcesoBolsasRequest extends FormRequest
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
            'orden_detalle_id' => 'required|exists:orden_compra_proveedor_detalles,id',
            'observacion' => 'nullable|string',

            'proceso_bolsas_id' => 'required|exists:proceso_bolsas,id',
            'proveedor_id' => 'required|exists:proveedores,id',

         
        ];
    }


    public function messages(): array
    {
        return [
            'orden_detalle_id.required' => 'El campo orden_detalle_id es obligatorio.',
            'orden_detalle_id.exists' => 'El orden_detalle_id seleccionado no es válido.',
            'observacion.required' => 'Agregue una observación.',
            'observacion.string' => 'El campo observacion debe ser un texto.',
            'proceso_bolsas_id.required' => 'Debes seleccionar un proceso.',
            'proceso_bolsas_id.exists' => 'El proceso_bolsas_id seleccionado no es válido.',
            'proveedor_id.required' => 'Debes seleccionar un proveedor.',
            'proveedor_id.exists' => 'El proveedor_id seleccionado no es válido.',
          
        ];
    }
}
