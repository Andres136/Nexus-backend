<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrdenTrabajoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'observaciones' => ['required', 'string'],
        ];

        // 🔹 Solo validar sede_id si viene en la solicitud
        if ($this->has('sede_id')) {
            $rules['sede_id'] = ['required', 'integer', Rule::exists('sedes', 'id')];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'observaciones.required' => 'El campo observaciones es obligatorio.',
            'observaciones.string' => 'El campo observaciones debe ser una cadena de texto.',
            'sede_id.required' => 'El campo sede es obligatorio.',
            'sede_id.integer' => 'El campo sede debe ser un número entero.',
            'sede_id.exists' => 'La sede seleccionada no existe en el sistema.',
        ];
    }

    /**
     * 🔸 Prepara los datos antes de la validación.
     * Si no viene "sede_id" en el request, lo toma del usuario autenticado.
     */
    protected function prepareForValidation()
    {
        if (!$this->has('sede_id') && $this->user()) {
            $this->merge([
                'sede_id' => $this->user()->sede_id,
            ]);
        }
    }
}

