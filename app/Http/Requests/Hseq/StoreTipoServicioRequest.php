<?php

namespace App\Http\Requests\Hseq;

use Illuminate\Foundation\Http\FormRequest;

class StoreTipoServicioRequest extends FormRequest
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
            'nombre' => 'required|unique:tipo_servicios,nombre|max:255',
            'descripcion' => 'nullable|string',
            'unidad_medida' => 'nullable|string|max:50',
        ];
    }


    public function messages()
    {
        return [
            'nombre.required' => 'El nombre del tipo de servicio es obligatorio.',
            'nombre.string' => 'El nombre del tipo de servicio debe ser una cadena de texto.',
            'nombre.max' => 'El nombre del tipo de servicio no puede exceder los 255 caracteres.',
            'nombre.unique' => 'Ya existe un tipo de servicio con ese nombre.',
            'descripcion.string' => 'La descripción del tipo de servicio debe ser una cadena de texto.',
            'unidad_medida.string' => 'La unidad de medida del tipo de servicio debe ser una cadena de texto.',
            'unidad_medida.max' => 'La unidad de medida del tipo de servicio no puede exceder los 50 caracteres.',
        ];
    }
}
