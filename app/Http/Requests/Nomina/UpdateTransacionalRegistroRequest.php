<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransacionalRegistroRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tipo_marcacion_id' => 'sometimes|exists:tipo_registros,id',
            'foto_referencia'   => 'sometimes|image|mimes:jpg,jpeg,png|max:5120',
            'marked_ad'         => 'sometimes|date_format:H:i:s',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_marcacion_id.exists' => 'El tipo de marcación no existe.',
            'foto_referencia.image'    => 'La foto de referencia debe ser una imagen.',
            'foto_referencia.mimes'    => 'La foto de referencia debe ser jpg, jpeg o png.',
            'foto_referencia.max'      => 'La foto de referencia no puede superar 5MB.',
            'marked_ad.date_format'    => 'La hora debe tener formato HH:MM:SS.',
        ];
    }
}
