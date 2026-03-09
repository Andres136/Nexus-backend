<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGestionCarteraRequest extends FormRequest
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
        'fecha_vencimiento' => 'nullable|date',
        'dias_credito' => 'nullable|integer|min:0|max:365',
        'observaciones' => 'nullable|string|max:500',
        'user_comercial_id' => 'nullable|exists:users,id',
        'valor_total' => 'nullable|numeric|min:0',
        'valor' => 'nullable|numeric|min:0',
       'base' => 'nullable|numeric|min:0',
        'iva' => 'nullable|numeric|min:0',
        'rete_renta' => 'nullable|numeric|min:0',
        'rete_ica' => 'nullable|numeric|min:0'
    ];
}
public function messages()
{
    return [
        'fecha_vencimiento.date' => 'La fecha de vencimiento debe ser una fecha válida.',
        'dias_credito.integer' => 'Los días de crédito deben ser un número entero.',
        'dias_credito.min' => 'Los días de crédito no pueden ser negativos.',
        'dias_credito.max' => 'Los días de crédito no pueden ser mayores a 365.',
        'observaciones.string' => 'Las observaciones deben ser una cadena de texto.',
        'observaciones.max' => 'Las observaciones no pueden exceder los 500 caracteres.',
        'user_comercial_id.exists' => 'El ID del usuario comercial no existe en la base de datos.',
        'valor_total.numeric' => 'El valor total debe ser un número.',
        'valor_total.min' => 'El valor total no puede ser negativo.',
        'valor.numeric' => 'El valor debe ser un número.',
        'valor.min' => 'El valor no puede ser negativo.',
        'base.numeric' => 'La base debe ser un número.',
        'base.min' => 'La base no puede ser negativa.',
        'iva.numeric' => 'El IVA debe ser un número.',
        'iva.min' => 'El IVA no puede ser negativo.',
        'rete_renta.numeric' => 'La retención en la fuente debe ser un número.',
        'rete_renta.min' => 'La retención en la fuente no puede ser negativa.',
        'rete_ica.numeric' => 'La retención ICA debe ser un número.',
        'rete_ica.min' => 'La retención ICA no puede ser negativa.'
    ];


}
