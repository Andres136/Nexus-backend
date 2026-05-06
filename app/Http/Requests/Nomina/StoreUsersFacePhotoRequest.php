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
            'photo'    => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'users_id.required' => 'El empleado es obligatorio.',
            'users_id.exists'   => 'El empleado no existe.',
            'photo.required'    => 'La foto es obligatoria.',
            'photo.integer'     => 'La foto debe ser un valor entero.',
        ];
    }
}
