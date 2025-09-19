<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class StoreBodegaRequest extends FormRequest
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
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'sede_id' => 'required|exists:sedes,id',
            'estado_id' => 'required|exists:estados,id',

        ];
    }


    public function messages()
    {
        return [
            'sede_id.required' => 'El campo sede es obligatorio.',
            'sede_id.exists' => 'La sede seleccionada no es válida.',
            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.string' => 'El campo nombre debe ser una cadena de texto.',
            'nombre.max' => 'El campo nombre no debe exceder los 255 caracteres.',
            'direccion.required' => 'El campo dirección es obligatorio.',
            'direccion.string' => 'El campo dirección debe ser una cadena de texto.',
            'direccion.max' => 'El campo dirección no debe exceder los 255 caracteres.',
        
        ];
    }
}
