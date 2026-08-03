<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class ChatbotLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_lead' => 'required|string|max:255',
            'email_lead' => 'required|email|max:255',
            'empresa_lead' => 'nullable|string|max:255',
            'telefono_lead' => 'required|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_lead.required' => 'El nombre es obligatorio.',
            'email_lead.required' => 'El correo es obligatorio.',
            'email_lead.email' => 'El correo no es válido.',
            'telefono_lead.required' => 'El teléfono es obligatorio.',
        ];
    }
}
