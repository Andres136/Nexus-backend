<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncapacidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_incapacidad'    => 'required|string|max:45',
            'identidad_medica_id' => 'required|exists:seguridad_socials,id',
            'inicio'              => 'required|date',
            'fin'                 => 'required|date|after:inicio',
            'soporte'             => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'status'              => 'boolean',
         
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_incapacidad.required'    => 'El tipo de incapacidad es obligatorio',
            'identidad_medica_id.required' => 'La entidad médica es obligatoria',
            'identidad_medica_id.exists'   => 'La entidad médica no existe',
            'inicio.required'              => 'La fecha de inicio es obligatoria',
            'fin.required'                 => 'La fecha de fin es obligatoria',
            'fin.after'                    => 'La fecha fin debe ser después del inicio',
            'soporte.file'                 => 'El soporte debe ser un archivo',
            'soporte.mimes'                => 'El soporte debe ser PDF o imagen',
            'soporte.required'             => 'El soporte es obligatorio',
        ];
    }
}