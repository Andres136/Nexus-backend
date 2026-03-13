<?php

namespace App\Http\Requests\Hseq;

use Illuminate\Foundation\Http\FormRequest;

class StoreResiduoRequest extends FormRequest
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
             'sede_id' => 'required|exists:sedes,id',
             'tipo_residuo_id' => 'required|exists:tipo_residuos,id',
             'cantidad' => 'required|numeric',
             'fecha' => 'required|date',
             'unidad_medida' => 'required|string|max:50',
             'observaciones' => 'nullable|string|max:255'
        ];
    }



    public function messages()
    {
        return [
            'sede_id.required' => 'La sede es obligatoria.',
            'sede_id.exists' => 'La sede seleccionada no existe.',
            'tipo_residuo_id.required' => 'El tipo de residuo es obligatorio.',
            'tipo_residuo_id.exists' => 'El tipo de residuo seleccionado no existe.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.numeric' => 'La cantidad debe ser un número.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha no es válida.',
            'unidad_medida.required' => 'La unidad de medida es obligatoria.',
            'unidad_medida.string' => 'La unidad de medida debe ser una cadena de texto.',
            'unidad_medida.max' => 'La unidad de medida no puede exceder los 50 caracteres.',
            'observaciones.string' => 'Las observaciones deben ser una cadena de texto.',
            'observaciones.max' => 'Las observaciones no pueden exceder los 255 caracteres.'
        ];
    }
}
