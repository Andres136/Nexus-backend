<?php

namespace App\Http\Requests\Crm;

use App\RolEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChatbotAsignarRequest extends FormRequest
{
    /**
     * La ruta ya está protegida por el middleware `es_responsable_del_departamento`.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role_id', RolEnum::EJECUTIVO_COMERCIAL->value),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Debes seleccionar un ejecutivo.',
            'user_id.exists' => 'Debes asignar la conversación a un ejecutivo comercial válido.',
        ];
    }
}
