<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class ChatbotMensajePublicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contenido' => 'required|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'contenido.required' => 'Escribe un mensaje antes de enviar.',
            'contenido.max' => 'El mensaje no puede superar los 2000 caracteres.',
        ];
    }
}
