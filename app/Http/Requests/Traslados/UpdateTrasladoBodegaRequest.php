<?php

namespace App\Http\Requests\Traslados;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrasladoBodegaRequest extends FormRequest
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
            'bodega_origen_id' => 'required|exists:bodegas,id',
            'bodega_destino_id' => 'required|exists:bodegas,id',
            'observaciones' => 'nullable|string|max:255',
            'detalles' => 'required|array',
            'detalles.*.producto_id' => 'required|exists:products,id',
            'detalles.*.cantidad' => 'required|numeric|min:1',
        ];
    }
    public function messages(): array
    {
        return [
            'bodega_origen_id.required' => 'La bodega de origen es obligatoria',
            'bodega_destino_id.required' => 'La bodega de destino es obligatoria',
            'observaciones.max' => 'Las observaciones no pueden tener más de 255 caracteres',
            'detalles.required' => 'Los detalles son obligatorios',
            'detalles.array' => 'Los detalles deben ser un array',
            'detalles.*.producto_id.required' => 'El ID del producto es obligatorio',
            'detalles.*.producto_id.exists' => 'El producto seleccionado no existe',
            'detalles.*.cantidad.required' => 'La cantidad es obligatoria',
            'detalles.*.cantidad.integer' => 'La cantidad debe ser un número entero',
            'detalles.*.cantidad.min' => 'La cantidad debe ser al menos 1',
        ];
    }
}
