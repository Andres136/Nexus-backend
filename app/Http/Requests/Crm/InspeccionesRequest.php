<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class InspeccionesRequest extends FormRequest
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
            //

            'vehiculo_id' => 'required|exists:vehiculos,id',
            'fecha' => 'nullable|date',
            'responsable' => 'required|string|max:255',
            'observaciones' => 'required|string|max:1000',
            'estado_general' => 'required|string|max:255',
        ];
    }
    public function messages()
    {
        return [
            'vehiculo_id.required' => 'Debes seleccionar un vehiculo.',
            'vehiculo_id.exists' => 'El vehiculo_id no existe en la base de datos.',
   
            'responsable.required' => 'Debes ingresar tu nombre.',
            'responsable.string' => 'El campo responsable debe ser una cadena de texto.',
            'responsable.max' => 'El campo responsable no puede tener más de 255 caracteres.',
            'observaciones.required' => 'Debes ingresar las observaciones.',
            'observaciones.string' => 'El campo observaciones debe ser una cadena de texto.',
            'observaciones.max' => 'El campo observaciones no puede tener más de 1000 caracteres.',
            'estado_general.required' => 'Selecciona un estado general.',

      
        ];
    }
}
