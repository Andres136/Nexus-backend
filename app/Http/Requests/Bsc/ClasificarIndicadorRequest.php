<?php

namespace App\Http\Requests\Bsc;

use Illuminate\Foundation\Http\FormRequest;

class ClasificarIndicadorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'perspectiva'          => 'nullable|string|exists:bsc_perspectivas,clave',
            'objetivo_estrategico' => 'nullable|string|max:255',
            'unidad'               => 'nullable|string|max:20',
            'calculo_key'          => 'nullable|string|max:255',
            'orden'                => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'perspectiva.exists' => 'La perspectiva seleccionada no existe.',
        ];
    }

    /** @return array<string, mixed> */
    public function datosClasificacion(): array
    {
        return $this->only([
            'perspectiva',
            'objetivo_estrategico',
            'unidad',
            'calculo_key',
            'orden',
        ]);
    }
}
