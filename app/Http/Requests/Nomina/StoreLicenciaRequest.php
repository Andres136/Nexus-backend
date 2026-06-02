<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StoreLicenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo'            => 'required|in:maternidad,paternidad',
            'inicio'          => 'required|date',
            'fin'             => 'required|date|after_or_equal:inicio',
            'dias_calendario' => 'required|integer|min:1|max:365',
            'soporte'         => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'motivo'          => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required'            => 'El tipo de licencia es obligatorio.',
            'tipo.in'                  => 'El tipo debe ser: maternidad o paternidad.',
            'inicio.required'          => 'La fecha de inicio es obligatoria.',
            'inicio.date'              => 'La fecha de inicio debe ser una fecha válida.',
            'fin.required'             => 'La fecha de fin es obligatoria.',
            'fin.date'                 => 'La fecha de fin debe ser una fecha válida.',
            'fin.after_or_equal'       => 'La fecha de fin debe ser igual o posterior al inicio.',
            'dias_calendario.required' => 'Los días calendario son obligatorios.',
            'dias_calendario.integer'  => 'Los días calendario deben ser un número entero.',
            'dias_calendario.min'      => 'Los días calendario deben ser al menos 1.',
            'dias_calendario.max'      => 'Los días calendario no pueden superar 365.',
            'soporte.file'             => 'El soporte debe ser un archivo.',
            'soporte.mimes'            => 'El soporte debe ser PDF, JPG, JPEG o PNG.',
            'soporte.max'              => 'El soporte no puede superar los 4 MB.',
            'motivo.max'               => 'El motivo no puede superar los 500 caracteres.',
        ];
    }
}
