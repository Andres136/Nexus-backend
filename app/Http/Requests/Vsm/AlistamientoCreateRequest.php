<?php

namespace App\Http\Requests\Vsm;

use App\Models\Vsm\Alistamiento;
use Illuminate\Foundation\Http\FormRequest;

class AlistamientoCreateRequest extends FormRequest
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
            'tipo_origen'      => 'required|in:OT,LIBRE',
            'orden_trabajo_id' => 'required_if:tipo_origen,OT|nullable|integer|exists:orden_de_trabajos,id',
            'producto_id'      => 'required_if:tipo_origen,LIBRE|nullable|integer|exists:products,id',
            'cantidad' => 'required|integer|min:0',
            'usuarios'         => 'required|array|min:1',
            'usuarios.*'       => 'integer|exists:users,id',
      
            'fecha'            => 'required|date',

        ];
    }
public function withValidator($validator)
{
    $validator->after(function ($validator) {

        $ordenTrabajoId = $this->input('orden_trabajo_id');

        $existeActivo = $ordenTrabajoId && Alistamiento::where('orden_trabajo_id', $ordenTrabajoId)
            ->whereIn('estado', [
                'INICIADO',
                'EN_PROGRESO',
                'PAUSADO',
                'REANUDADO'
            ])
            ->exists();

        if ($existeActivo) {
            $validator->errors()->add(
                'orden_trabajo_id',
                'La orden de trabajo ya tiene un alistamiento activo.'
            );
        }
    });
}


    public function messages(): array
    {
        return [
            'orden_trabajo_id.required' => 'La orden de trabajo es obligatoria.',
            'orden_trabajo_id.integer' => 'La orden de trabajo debe ser un número entero.',
            'orden_trabajo_id.exists' => 'La orden de trabajo seleccionada no existe.',
            'tipo_origen.required' => 'Debe seleccionar el origen del rendimiento.',
            'tipo_origen.in' => 'El origen seleccionado no es válido.',
            'producto_id.required_if' => 'El producto es obligatorio para un rendimiento libre.',
            'producto_id.exists' => 'El producto seleccionado no existe.',


            'cantidad.required' => 'La cantidad alistada es obligatoria.',
            'cantidad.integer' => 'La cantidad alistada debe ser un número entero.',
            'cantidad.numeric' => 'La cantidad alistada debe ser un número.',
            'cantidad.min' => 'La cantidad alistada no puede ser negativa.',

            'usuarios.required' => 'Debe asignar al menos un usuario al alistamiento.',
            'usuarios.array' => 'El campo de usuarios debe ser un arreglo.',
            'usuarios.min' => 'Debe asignar al menos un usuario al alistamiento.',
            'usuarios.*.integer' => 'Cada usuario debe ser un número entero.',

            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha no es válida.',
        ];
    }
}
