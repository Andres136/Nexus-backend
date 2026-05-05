<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIncapacidadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            // sometimes: solo valida si el campo viene en la petición
            'tipo_incapacidad'    => 'sometimes|string|max:45',
            'identidad_medica_id' => 'sometimes|exists:seguridad_socials,id',
            'inicio'              => 'sometimes|date',
            'fin'                 => 'sometimes|date|after:inicio',
            'soporte'             => 'sometimes|string|max:45',
            'status'              => 'sometimes|boolean',
            'user_reviso_id'      => 'sometimes|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'identidad_medica_id.exists' => 'La entidad médica no existe',
            'fin.after'                  => 'La fecha fin debe ser después del inicio',
            'user_reviso_id.exists'      => 'El revisor no existe',
        ];
    }
}

