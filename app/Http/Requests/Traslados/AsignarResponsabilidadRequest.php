<?php

namespace App\Http\Requests\Traslados;

use Illuminate\Foundation\Http\FormRequest;

class AsignarResponsabilidadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',

            'bodega_id' => 'nullable|exists:bodegas,id',
            'sede_id' => 'nullable|exists:sedes,id',
            'activo' => 'boolean',
            'fecha_asignacion' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
        ];
    }


    public function messages(): array
    {
        return [
            'user_id.required' => 'El ID del usuario es obligatorio.',
            'bodega_id.exists' => 'La bodega seleccionada no es válida.',
            'sede_id.exists' => 'La sede seleccionada no es válida.',
            'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
            'fecha_asignacion.date' => 'La fecha de asignación debe ser una fecha válida.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
        ];
    }
}