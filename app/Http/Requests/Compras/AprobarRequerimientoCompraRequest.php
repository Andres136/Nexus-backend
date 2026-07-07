<?php

namespace App\Http\Requests\Compras;

use Illuminate\Foundation\Http\FormRequest;

class AprobarRequerimientoCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comentario' => 'nullable|string|max:1000',
            'detalles' => 'nullable|array',
            'detalles.*.id' => 'required_with:detalles|integer|exists:requerimiento_compra_detalles,id',
            'detalles.*.cantidad_aprobada' => 'required_with:detalles|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'comentario.string' => 'El comentario debe ser una cadena de texto.',
            'comentario.max' => 'El comentario no debe exceder los 1000 caracteres.',
            'detalles.array' => 'Los detalles deben ser un arreglo.',
            'detalles.*.id.required_with' => 'El ID del detalle es obligatorio cuando se proporcionan detalles.',
            'detalles.*.id.integer' => 'El ID del detalle debe ser un número entero.',
            'detalles.*.id.exists' => 'El ID del detalle proporcionado no existe.',
            'detalles.*.cantidad_aprobada.required_with' => 'La cantidad aprobada es obligatoria cuando se proporcionan detalles.',
            'detalles.*.cantidad_aprobada.numeric' => 'La cantidad aprobada debe ser un número.',
            'detalles.*.cantidad_aprobada.min' => 'La cantidad aprobada no puede ser negativa.',
        ];
    }
}
