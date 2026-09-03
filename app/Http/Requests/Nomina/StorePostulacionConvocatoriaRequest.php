<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StorePostulacionConvocatoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cargo_interes' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'cargo_interes.required' => 'Debes indicar el cargo de tu interés.',
        ];
    }
}
