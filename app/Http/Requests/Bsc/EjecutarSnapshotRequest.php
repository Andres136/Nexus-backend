<?php

namespace App\Http\Requests\Bsc;

use Illuminate\Foundation\Http\FormRequest;

class EjecutarSnapshotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'periodo' => 'nullable|date_format:Y-m',
        ];
    }

    public function periodo(): ?string
    {
        return $this->input('periodo') ?: null;
    }
}
