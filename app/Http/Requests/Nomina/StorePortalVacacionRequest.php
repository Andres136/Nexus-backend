<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StorePortalVacacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'dias_habiles' => 'required|integer|min:1|max:30',
            'tipo' => 'required|in:ordinarias,compensadas',
            'motivo' => 'nullable|string|max:255',
        ];
    }
}
