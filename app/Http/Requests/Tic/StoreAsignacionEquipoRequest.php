<?php

namespace App\Http\Requests\Tic;

use App\Models\Tic\Asignaciones;
use Illuminate\Foundation\Http\FormRequest;

class StoreAsignacionEquipoRequest extends FormRequest
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
            'sede_id' => 'required|exists:sedes,id',
            'producto_id' => [
            'required',
            'exists:products,id',
            function ($attribute, $value, $fail) {
                $existe = Asignaciones::where('producto_id', $value)
                    ->where('activo', true)
                    ->exists();

                if ($existe) {
                    $fail('Este equipo ya se encuentra asignado.');
                }
            },
        ],
            'empresa_id' => 'required|exists:empresas,id',
            'fecha_asignacion' => 'required|date',
            'usuario_asignacion_id' => 'required|exists:users,id',
            'observaciones' => 'nullable|string',
        ];
    }


    public function messages()
    {
        return [
            'sede_id.required' => 'La sede es obligatoria.',
            'sede_id.exists' => 'La sede seleccionada no existe.',
            'usuario_id.required' => 'El usuario es obligatorio.',
            'usuario_id.exists' => 'El usuario seleccionado no existe.',
            'producto_id.required' => 'El producto es obligatorio.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
            'producto_id.unique' => 'Este equipo ya se encuentra asignado.',
            'empresa_id.required' => 'La empresa es obligatoria.',
            'empresa_id.exists' => 'La empresa seleccionada no existe.',
            'fecha_asignacion.required' => 'La fecha de asignación es obligatoria.',
            'fecha_asignacion.date' => 'La fecha de asignación debe ser una fecha válida.',
            'usuario_asignacion_id.required' => 'Seleccione el usuario a quien se le asigna el equipo.',
            'usuario_asignacion_id.exists' => 'El usuario seleccionado para la asignación no existe.',
            'observaciones.string' => 'Las observaciones deben ser una cadena de texto.',
        ];
    }
}
