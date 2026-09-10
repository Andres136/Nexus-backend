<?php

namespace App\Http\Requests\Bsc;

use Illuminate\Foundation\Http\FormRequest;

class DashboardBscRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'periodo'   => 'nullable|date_format:Y-m',
            'historial' => 'nullable|integer|min:1|max:24',
        ];
    }

    public function periodo(): string
    {
        return $this->query('periodo') ?: now()->format('Y-m');
    }

    public function historial(): int
    {
        return (int) ($this->query('historial') ?: 6);
    }
}
