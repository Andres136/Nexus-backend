<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class CotizacionRequest extends FormRequest
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
            'empresa' => 'required|in:setasplast,global',
            'observaciones' => 'nullable|string',

            'detalles' => 'required|array|min:1',
            'detalles.*.largo_cm' => 'nullable|numeric|min:0',
            'detalles.*.ancho_cm' => 'nullable|numeric|min:0',
            'detalles.*.calibre' => 'nullable|numeric|min:0',
            'detalles.*.numero_bolsas' => 'nullable|integer|min:1',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
          
            'detalles.*.descripcion' => 'nullable|string',
            'detalles.*.cliente_clb' => 'nullable|string',
            'detalles.*.observaciones' => 'nullable|string',
        ];
    }
    public function messages(): array
    {
        return [
            'cliente_id.required' => 'El cliente es obligatorio.',
            'empresa.required' => 'Debe seleccionar una empresa.',
            'detalles.required' => 'Debe agregar al menos un detalle.',
            'detalles.*.cantidad.required' => 'La cantidad es obligatoria.',
            'detalles.*.cantidad.numeric' => 'La cantidad debe ser un número.',
            'detalles.*.cantidad.min' => 'La cantidad debe ser mayor a 0.',
            'detalles.*.precio_total.numeric' => 'El precio total debe ser un número.',

        ];
    }
}
