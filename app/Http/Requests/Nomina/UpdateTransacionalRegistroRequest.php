<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransacionalRegistroRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'users_id'          => 'sometimes|integer|exists:users,id',
            'kiosk_device_id'   => 'sometimes|integer|exists:kiosko_devices,id',
            'tipo_marcacion_id' => 'sometimes|integer|exists:tipo_registros,id',
            'foto_referencia'   => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:5120',
            'marked_ad'         => 'sometimes|nullable|date_format:Y-m-d H:i:s',
        ];
    }

    public function messages(): array
    {
        return [
            'users_id.exists'          => 'El empleado no existe.',
            'users_id.integer'         => 'El empleado debe ser un número.',
            'kiosk_device_id.exists'   => 'El dispositivo kiosko no existe.',
            'kiosk_device_id.integer'  => 'El dispositivo kiosko debe ser un número.',
            'tipo_marcacion_id.exists' => 'El tipo de marcación no existe.',
            'foto_referencia.image'    => 'La foto de referencia debe ser una imagen.',
            'foto_referencia.mimes'    => 'La foto debe ser jpg, jpeg o png.',
            'foto_referencia.max'      => 'La foto no puede superar 5MB.',
            'marked_ad.date_format'    => 'La fecha y hora debe tener formato YYYY-MM-DD HH:MM:SS.',
        ];
    }
}
