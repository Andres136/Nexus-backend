<?php

namespace App\Http\Requests\Crm\Orden_servicio;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProcesosOrdenServicioRequest extends FormRequest
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
            'observacion' => 'nullable|string',
          
             // Detalles de procesos
            'detalles' => 'required|array',
            'detalles.*.observacion_id' => 'required|exists:orden_detalle_observaciones,id',
            'detalles.*.cantidad' => 'required|numeric|min:1',
            'detalles.*.proceso_bolsas_id' => 'required|exists:proceso_bolsas,id',
        ];
    }
  public function messages()
{
    return [

        'detalles.required' => 'Debe enviar al menos un detalle.',
        'detalles.*.observacion_id.required' => 'La observación es obligatoria.',
        'detalles.*.cantidad.min' => 'La cantidad debe ser mayor que 0.',
        'detalles.*.proceso_bolsas_id.exists' => 'El proceso seleccionado no existe.'

    ];
}

    
}
