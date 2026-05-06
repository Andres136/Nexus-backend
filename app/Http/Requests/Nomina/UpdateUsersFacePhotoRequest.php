<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUsersFacePhotoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'users_id' => 'sometimes|exists:users,id',
            'photo'    => 'sometimes|image|mimes:jpg,jpeg,png|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'users_id.exists' => 'El empleado no existe.',
            'photo.image'     => 'El archivo debe ser una imagen.',
            'photo.mimes'     => 'La imagen debe ser jpg, jpeg o png.',
            'photo.max'       => 'La imagen no puede superar 5MB.',
        ];
    }
}
