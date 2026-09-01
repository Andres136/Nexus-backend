<?php

namespace App\Http\Requests\Bsc;

use Illuminate\Foundation\Http\FormRequest;

class ListarIndicadoresBscRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'departamento_id' => 'nullable|integer|exists:departamentos,id',
            'search'          => 'nullable|string|max:100',
        ];
    }

    public function departamentoId(): ?int
    {
        return $this->filled('departamento_id') ? (int) $this->query('departamento_id') : null;
    }

    public function search(): ?string
    {
        return $this->query('search') ?: null;
    }
}
