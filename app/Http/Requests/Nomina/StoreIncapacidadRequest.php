<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncapacidadRequest extends FormRequest
{
    // ¿Quién puede hacer esta petición?
    public function authorize(): bool
    {
        return true; // luego aquí va la lógica de permisos
    }

    public function rules(): array
    {
        return [
            'tipo_incapacidad'    => 'required|string|max:45',
            'identidad_medica_id' => 'required|exists:seguridad_socials,id',
            // exists: verifica que el id exista en la BD
            'inicio'              => 'required|date',
            'fin'                 => 'required|date|after:inicio',
            // after: fin debe ser después de inicio
            'soporte'             => 'required|string|max:45',
            'status'              => 'boolean',
            'user_id'             => 'required|exists:users,id',
            'user_reviso_id'      => 'required|exists:users,id',
        ];
    }

    // Mensajes personalizados en español
    public function messages(): array
    {
        return [
            'tipo_incapacidad.required'    => 'El tipo de incapacidad es obligatorio',
            'identidad_medica_id.required' => 'La entidad médica es obligatoria',
            'identidad_medica_id.exists'   => 'La entidad médica no existe',
            'inicio.required'              => 'La fecha de inicio es obligatoria',
            'fin.required'                 => 'La fecha de fin es obligatoria',
            'fin.after'                    => 'La fecha fin debe ser después del inicio',
            'soporte.required'             => 'El número de soporte es obligatorio',
            'user_id.required'             => 'El empleado es obligatorio',
            'user_id.exists'               => 'El empleado no existe',
            'user_reviso_id.exists'        => 'El revisor no existe',
        ];
    }
}