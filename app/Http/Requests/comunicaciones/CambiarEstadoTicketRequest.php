<?php

namespace App\Http\Requests\comunicaciones;

use Illuminate\Foundation\Http\FormRequest;

class CambiarEstadoTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', 'in:pendiente,en_proceso,cerrado'],
            'comentario' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
