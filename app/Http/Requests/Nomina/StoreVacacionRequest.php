<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StoreVacacionRequest extends FormRequest
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
            'user_id'      => 'required|integer|exists:users,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
            'dias_habiles' => 'required|integer|min:1|max:30',
            'tipo'         => 'required|in:ordinarias,compensadas',
            'motivo'       => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required'       => 'El empleado es obligatorio.',
            'user_id.exists'         => 'El empleado no existe.',
            'fecha_inicio.required'  => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date'      => 'La fecha de inicio no es válida.',
            'fecha_fin.required'     => 'La fecha de fin es obligatoria.',
            'fecha_fin.date'         => 'La fecha de fin no es válida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'dias_habiles.required'  => 'Los días hábiles son obligatorios.',
            'dias_habiles.integer'   => 'Los días hábiles deben ser un número entero.',
            'dias_habiles.min'       => 'Debe solicitar al menos 1 día hábil.',
            'dias_habiles.max'       => 'No puede solicitar más de 30 días hábiles.',
            'tipo.required'          => 'El tipo de vacación es obligatorio.',
            'tipo.in'                => 'El tipo debe ser: ordinarias o compensadas.',
        ];
    }
}
