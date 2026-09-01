<?php

namespace App\Http\Requests\Bsc;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarPerspectivaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        // multipart manda "departamentos[]" como strings o un JSON; normaliza.
        $deptos = $this->input('departamentos');

        if (is_string($deptos)) {
            $decoded = json_decode($deptos, true);
            $this->merge(['departamentos' => is_array($decoded) ? $decoded : array_filter(explode(',', $deptos))]);
        }
    }

    public function rules(): array
    {
        return [
            'nombre'          => 'sometimes|string|max:255',
            'color'           => 'sometimes|string|max:20',
            'orden'           => 'sometimes|integer|min:0',
            'icono'           => 'sometimes|image|max:2048',
            'departamentos'   => 'sometimes|array',
            'departamentos.*' => 'integer|exists:departamentos,id',
        ];
    }

    /** Campos de texto (sin archivo ni relaciones). @return array<string, mixed> */
    public function datosPerspectiva(): array
    {
        return $this->only(['nombre', 'color', 'orden']);
    }

    /** Ids de departamentos responsables, o null si no se enviaron. @return int[]|null */
    public function departamentos(): ?array
    {
        return $this->has('departamentos')
            ? array_map('intval', $this->input('departamentos', []))
            : null;
    }
}
