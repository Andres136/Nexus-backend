<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class AsignarClientesExcelRequest extends FormRequest
{
    /**
     * La ruta ya está protegida por el middleware `es_responsable_del_departamento`.
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
            'nits' => 'required|array|min:1',
            'nits.*' => 'required|string|max:20',
            'user_id' => 'required|integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nits.required' => 'Se requiere al menos un cliente (NIT) para asignar.',
            'nits.array' => 'El archivo de clientes debe ser un array.',
            'user_id.required' => 'Debes seleccionar un responsable.',
            'user_id.exists' => 'El responsable seleccionado no existe.',
        ];
    }
}
