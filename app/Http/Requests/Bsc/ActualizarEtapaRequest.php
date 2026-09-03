<?php

namespace App\Http\Requests\Bsc;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarEtapaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'estado'         => 'sometimes|in:pendiente,en_progreso,completada',
            'responsable_id' => 'sometimes|nullable|integer|exists:users,id',
            'fecha_objetivo' => 'sometimes|nullable|date',
            'notas'          => 'sometimes|nullable|string|max:2000',
        ];
    }

    /** @return array<string, mixed> */
    public function datosEtapa(): array
    {
        return $this->only(['estado', 'responsable_id', 'fecha_objetivo', 'notas']);
    }
}
