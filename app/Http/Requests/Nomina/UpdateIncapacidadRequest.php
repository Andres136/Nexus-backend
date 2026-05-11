<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIncapacidadRequest extends FormRequest
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
        'tipo_incapacidad'    => 'sometimes|string|max:45',
        'identidad_medica_id' => 'sometimes|exists:seguridad_socials,id',
        'inicio'              => 'sometimes|date',
        'fin'                 => 'sometimes|date|after_or_equal:inicio',

        // Solo PDF
        'soporte'             => 'sometimes|file|mimes:pdf|max:5120',

        'status'              => 'sometimes|boolean',
        'user_reviso_id'      => 'sometimes|exists:users,id',
    ];
}

public function messages(): array
{
    return [
        'tipo_incapacidad.max'       => 'El tipo de incapacidad no debe superar 45 caracteres.',
        'identidad_medica_id.exists' => 'La entidad médica no existe.',
        'inicio.date'                => 'La fecha de inicio no es válida.',
        'fin.date'                   => 'La fecha final no es válida.',
        'fin.after_or_equal'         => 'La fecha fin debe ser igual o posterior a la fecha de inicio.',

        'soporte.file'               => 'El soporte debe ser un archivo válido.',
        'soporte.mimes'              => 'El soporte debe ser únicamente en formato PDF.',
        'soporte.max'                => 'El soporte PDF no debe superar 5MB.',

        'status.boolean'             => 'El estado debe ser verdadero o falso.',
        'user_reviso_id.exists'      => 'El revisor no existe.',
    ];
}


}

