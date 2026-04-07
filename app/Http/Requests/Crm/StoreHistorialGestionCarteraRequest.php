<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class StoreHistorialGestionCarteraRequest extends FormRequest
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
        'gestion_cartera_id' => 'required|integer|exists:gestion_cartera,id',

        //  NUEVO (ARRAY DE ARCHIVOS)
        'soportes' => 'required|array',
        'soportes.*' => 'file|mimes:jpg,jpeg,png,pdf|max:2048',

        'observacion' => 'nullable|string',

        //  puedes dejarlo así o actualizarlo (te explico abajo)
        'tipo' => 'required|string|in:LLAMADA,EMAIL,VISITA,PROMESA_PAGO,OTRO',

        'fecha_compromiso' => 'nullable|date'
    ];
}

    public function messages(): array
{
    return [
        'gestion_cartera_id.required' => 'El ID de gestión de cartera es requerido',
        'gestion_cartera_id.exists' => 'La gestión de cartera no existe',

        'soportes.required' => 'Los soportes son requeridos',
        'soportes.array' => 'Los soportes deben ser un arreglo de archivos',
        'soportes.*.file' => 'Cada soporte debe ser un archivo válido',
        'soportes.*.mimes' => 'Los soportes deben ser jpg, jpeg, png o pdf',
        'soportes.*.max' => 'Cada archivo no debe superar los 2MB',

        'tipo.required' => 'El tipo es requerido',
        'tipo.in' => 'Tipo inválido (LLAMADA, EMAIL, VISITA, PROMESA_PAGO, OTRO)',

        'fecha_compromiso.date' => 'La fecha de compromiso debe ser válida'
    ];
}
}
