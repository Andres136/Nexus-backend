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
            'orden_trabajo_id' => 'required|integer|exists:orden_de_trabajos,id',
            'cantidad' => 'required|numeric|min:0',
            'usuarios'         => 'required|array|min:1',
            'usuarios.*'       => 'integer|exists:users,id',
      
            'fecha'            => 'required|date',

        ];
    }
public function withValidator($validator)
{
    $validator->after(function ($validator) {

        $ordenTrabajoId = $this->input('orden_trabajo_id');

        $existeActivo = Alistamiento::where('orden_trabajo_id', $ordenTrabajoId)
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


            'cantidad.required' => 'La cantidad alistada es obligatoria.',
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
