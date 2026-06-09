<?php

namespace App\Http\Requests\Nomina;

use App\Services\Nomina\LiquidacionPrestacionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LiquidarPrestacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'        => ['required', 'integer', 'exists:users,id'],
            'tipo'           => ['required', Rule::in(array_keys(LiquidacionPrestacionService::tipos()))],
            'periodo_inicio' => ['required', 'date'],
            'periodo_fin'    => ['required', 'date', 'after_or_equal:periodo_inicio'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.in' => 'El tipo debe ser: ' . implode(', ', array_keys(LiquidacionPrestacionService::tipos())),
        ];
    }
}
