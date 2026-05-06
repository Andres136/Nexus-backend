<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StoreUsersFacePhotoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'users_id' => 'required|exists:users,id',
            'photo'    => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'users_id.required' => 'El empleado es obligatorio.',
            'users_id.exists'   => 'El empleado no existe.',
            'photo.required'    => 'La foto es obligatoria.',
            'photo.image'       => 'El archivo debe ser una imagen.',
            'photo.mimes'       => 'La imagen debe ser jpg, jpeg o png.',
            'photo.max'         => 'La imagen no puede superar 5MB.',
        ];
    }
}
