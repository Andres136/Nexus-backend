<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StoreJornadaLaboralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'horas_semanales' => 'required|integer|min:1|max:48',
            'nombre'          => 'required|integer',
            'status'          => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'horas_semanales.required' => 'Las horas semanales son obligatorias',
            'horas_semanales.integer'  => 'Las horas semanales deben ser un número entero',
            'horas_semanales.min'      => 'Las horas semanales deben ser mínimo 1',
            'horas_semanales.max'      => 'Las horas semanales no pueden superar 48',
            'nombre.required'          => 'El nombre es obligatorio',
            'nombre.integer'           => 'El nombre debe ser un número entero',
        ];
    }
}
