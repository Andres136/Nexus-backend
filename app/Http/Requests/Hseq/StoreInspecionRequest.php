<?php

namespace App\Http\Requests\Hseq;

use Illuminate\Foundation\Http\FormRequest;

class StoreInspecionRequest extends FormRequest
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
            'tipo_inspeccion_id' => 'required|exists:tipo_inspecciones,id',
            'fecha' => 'required|date',
      
          
            'observaciones' => 'nullable|string',
        ];
    }


    public function messages()
    {
        return [
            'sede_id.required' => 'El campo sede es obligatorio.',
            'sede_id.exists' => 'La sede seleccionada no existe.',
            'tipo_inspeccion_id.required' => 'El campo tipo de inspección es obligatorio.',
            'tipo_inspeccion_id.exists' => 'El tipo de inspección seleccionado no existe.',
            'fecha.required' => 'El campo fecha es obligatorio.',
            'fecha.date' => 'El campo fecha debe ser una fecha válida.',
   
          

            'observaciones.string' => 'El campo observaciones debe ser una cadena de texto.',
        ];
    }
}
