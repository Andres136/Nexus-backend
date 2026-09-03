<?php

namespace App\Http\Requests\Bsc;

use Illuminate\Foundation\Http\FormRequest;

class ListarPlanesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'periodo'     => 'nullable|date_format:Y-m',
            'perspectiva' => 'nullable|string|exists:bsc_perspectivas,clave',
        ];
    }

    public function periodo(): ?string
    {
        return $this->query('periodo') ?: null;
    }

    public function perspectiva(): ?string
    {
        return $this->query('perspectiva') ?: null;
    }
}
