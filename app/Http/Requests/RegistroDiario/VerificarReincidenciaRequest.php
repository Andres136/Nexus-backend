<?php

namespace App\Http\Requests\RegistroDiario;

use Illuminate\Foundation\Http\FormRequest;

class VerificarReincidenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descripcion' => 'required|string|max:2000',
            'departamento_id' => 'nullable|integer|exists:departamentos,id',
            'causa' => 'nullable|string|max:2000',
            'clasificacion' => 'nullable|string|in:NO_CONFORMIDAD,OPORTUNIDAD_MEJORA',
        ];
    }

    public function messages(): array
    {
        return [
            'descripcion.required' => 'Describe la No Conformidad para poder verificar la reincidencia.',
            'departamento_id.exists' => 'El departamento seleccionado no existe.',
        ];
    }
}
