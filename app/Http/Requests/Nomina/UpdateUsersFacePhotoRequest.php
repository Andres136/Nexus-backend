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
            'photo'    => 'sometimes|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'users_id.exists'  => 'El empleado no existe.',
            'photo.integer'    => 'La foto debe ser un valor entero.',
        ];
    }
}
