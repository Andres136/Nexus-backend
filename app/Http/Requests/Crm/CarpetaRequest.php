<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class CarpetaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'    => 'required|string|max:255',
            'parent_id' => 'nullable|exists:carpetas,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'    => 'El nombre es requerido',
            'nombre.max'         => 'El nombre no debe exceder 255 caracteres',
            'parent_id.exists'   => 'La carpeta padre no existe',
        ];
    }
}
