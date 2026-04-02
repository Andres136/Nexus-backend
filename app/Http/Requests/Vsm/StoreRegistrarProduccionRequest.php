<?php

namespace App\Http\Requests\Vsm;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegistrarProduccionRequest extends FormRequest
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
            'alistamiento_id' => 'required|exists:alistamiento,id',
            'detalle_id'      => 'required|exists:alistamiento_detalles,id',
            'cantidad_alistada'        => 'required|integer|min:1'
        ];
    }

    public function messages(): array
    {
        return [
            'alistamiento_id.required' => 'El ID del alistamiento es obligatorio.',
            'alistamiento_id.exists' => 'El alistamiento especificado no existe.',
            'detalle_id.required' => 'El ID del detalle es obligatorio.',
            'detalle_id.exists' => 'El detalle especificado no existe.',
            'cantidad_alistada.required' => 'La cantidad alistada es obligatoria.',
            'cantidad_alistada.integer' => 'La cantidad alistada debe ser un número entero.',
            'cantidad_alistada.min' => 'La cantidad alistada debe ser al menos 1.'
        ];
    }
}
