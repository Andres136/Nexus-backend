<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrdenComprasHistorialRequest extends FormRequest
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
        'orden_compra_id' => 'required|exists:orden__compras,id',
        'fecha_nueva' => 'required|date',
        'observacion' => 'nullable|string|max:500',
    ];
}

        public function messages()
        {
            return [
                'orden_compra_id.required' => 'El ID de la orden de compra es obligatorio.',
                'orden_compra_id.exists' => 'La orden de compra especificada no existe.',
                'fecha_nueva.required' => 'La nueva fecha de entrega es obligatoria.',
                'fecha_nueva.date' => 'La nueva fecha de entrega debe ser una fecha válida.',
                'observacion.string' => 'La observación debe ser un texto.',
                'observacion.max' => 'La observación no puede exceder los 500 caracteres.',
            ];
        }
}
