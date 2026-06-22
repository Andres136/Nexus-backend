<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class GestionVacacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user
            && ($user->role_id == 1 || $user->esResponsableDeSuDepartamento());
    }

    public function rules(): array
    {
        return [
            'observacion' => 'nullable|string|max:255',
        ];
    }
}
