<?php

namespace App\Http\Requests\contabilidad;

use App\ImpuestoOperacionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreimpuestoRequest extends FormRequest
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
            'nombre' => 'required|string|max:255',
            'porcentaje' => 'required|numeric|decimal:0,6|min:0|max:100',
            'operacion' => ['required', Rule::enum(ImpuestoOperacionEnum::class)],
        ];
    }

    public function messages()
    {
        return [
            'nombre.required' => 'El nombre del impuesto es obligatorio.',
            'nombre.unique' => 'El nombre del impuesto ya existe.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
            'porcentaje.required' => 'El porcentaje del impuesto es obligatorio.',
            'porcentaje.numeric' => 'El porcentaje debe ser un número.',
            'porcentaje.decimal' => 'El porcentaje puede tener máximo 6 decimales.',
            'porcentaje.min' => 'El porcentaje no puede ser negativo.',
            'porcentaje.max' => 'El porcentaje no puede exceder el 100%.',
            'operacion.required' => 'Debes indicar si el impuesto suma o resta.',
            'operacion.enum' => 'La operación del impuesto debe ser suma o resta.',
        ];
    }
}
