<?php

namespace App\Http\Requests\Tic;

use Illuminate\Foundation\Http\FormRequest;

class StoreMantenimientoEquiposRequest extends FormRequest
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
            'producto_id' => 'required|exists:products,id',
            'empresa_id' => 'required|exists:empresas,id',
         
            'tipo' => 'required|in:preventivo,correctivo',
            'fecha_programada' => 'nullable|date',
            'fecha_ejecucion' => 'nullable|date',
            'observaciones' => 'nullable|string',
           
            'costo' => 'nullable|numeric',
        ];
    }

     public function messages()
    {
        return [
            'sede_id.required' => 'La sede es obligatoria.',
            'sede_id.exists' => 'La sede seleccionada no existe.',
            'producto_id.required' => 'El producto es obligatorio.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
            'empresa_id.required' => 'La empresa es obligatoria.',
            'empresa_id.exists' => 'La empresa seleccionada no existe.',
          
            'tipo.required' => 'El tipo de mantenimiento es obligatorio.',
            'tipo.in' => 'El tipo de mantenimiento debe ser preventivo o correctivo.',
            'fecha_programada.date' => 'La fecha programada debe ser una fecha válida.',
            'fecha_ejecucion.date' => 'La fecha de ejecución debe ser una fecha válida.',
            'observaciones.string' => 'Las observaciones deben ser un texto válido.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser pendiente, en_proceso o completado.',
            'costo.numeric' => 'El costo debe ser un número válido.',
        ];
    }
}
