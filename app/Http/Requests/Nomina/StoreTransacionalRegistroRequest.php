<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransacionalRegistroRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'users_id'          => 'required|exists:users,id',
            'kiosk_device_id'   => 'required|exists:kiosko_devices,id',
            'tipo_marcacion_id' => 'required|exists:tipo_registros,id',
            'foto_referencia'   => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'marked_ad'         => 'required|date_format:H:i:s',
        ];
    }

    public function messages(): array
    {
        return [
            'users_id.required'          => 'El empleado es obligatorio.',
            'users_id.exists'            => 'El empleado no existe.',
            'kiosk_device_id.required'   => 'El dispositivo kiosko es obligatorio.',
            'kiosk_device_id.exists'     => 'El dispositivo kiosko no existe.',
            'tipo_marcacion_id.required' => 'El tipo de marcación es obligatorio.',
            'tipo_marcacion_id.exists'   => 'El tipo de marcación no existe.',
            'foto_referencia.required'   => 'La foto de referencia es obligatoria.',
            'foto_referencia.image'      => 'La foto de referencia debe ser una imagen.',
            'foto_referencia.mimes'      => 'La foto de referencia debe ser jpg, jpeg o png.',
            'foto_referencia.max'        => 'La foto de referencia no puede superar 5MB.',
            'marked_ad.required'         => 'La hora de marcación es obligatoria.',
            'marked_ad.date_format'      => 'La hora debe tener formato HH:MM:SS.',
        ];
    }
}
