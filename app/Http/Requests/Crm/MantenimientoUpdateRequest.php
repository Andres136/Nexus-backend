<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class MantenimientoUpdateRequest extends FormRequest
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
            'vehiculo_id' => 'required|exists:vehiculos,id', 
            'fecha_programada' => 'required|date',
            'fecha_realizado' => 'nullable|date',
            'taller' => 'required|string|max:255',
            'descripcion_trabajo' => 'required|string|max:1000',
            'costo' => 'required|numeric|min:0',
            'kilometro_programado' => 'required|numeric|min:0',
            'tipo_mantenimiento' => 'required|string|max:255',
            'archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // Validar el archivo
            'kilometraje_actual' => 'required|integer|min:0', // Permitir
        ];

    }

    public function messages()
    {
        return [
            'vehiculo_id.required' => 'El campo vehiculo_id es obligatorio.',
            'vehiculo_id.exists' => 'El vehiculo_id no existe en la base de datos.',
            'fecha_programada.required' => 'El campo fecha_programada es obligatorio.',
            'fecha_programada.date' => 'El campo fecha_programada debe ser una fecha válida.',
            'taller.required' => 'El campo taller es obligatorio.',
            'descripcion_trabajo.required' => 'El campo descripcion_trabajo es obligatorio.',
            'costo.required' => 'El campo costo es obligatorio.',
            'kilometro_programado.required' => 'El campo kilometraje_programado es obligatorio.',
            'tipo_mantenimiento.required' => 'El campo tipo_mantenimiento es obligatorio.',
            'archivo.file' => 'El campo archivo debe ser un archivo.',
            'archivo.mimes' => 'El campo archivo debe ser un archivo de tipo: pdf   , jpg, jpeg, png.',
            'archivo.max' => 'El tamaño máximo del archivo es de 10MB.',
            'kilometraje_actual.required' => 'El campo kilometraje_actual es obligatorio.',
            'kilometraje_actual.integer' => 'El campo kilometraje_actual debe ser un número entero.',
            'kilometraje_actual.min' => 'El campo kilometraje_actual no puede ser menor a 0.',
        ];
    }
}
