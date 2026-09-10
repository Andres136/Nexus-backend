<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class MantenimientoRequest extends FormRequest
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
            'tipo_mantenimiento' => 'required|string|max:255',
            'fecha_programada' => 'required|date',
            'fecha_realizado' => 'nullable|date',
            'taller' => 'nullable|string|max:255',
            'descripcion_trabajo' => 'nullable|string|max:1000',
            'costo' => 'nullable|numeric|min:0',
            'kilometro_programado' => 'nullable|string|max:255',
            'archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // El soporte solo es obligatorio al ejecutar (update)
            'kilometraje_actual' => 'nullable|integer|min:0',
        ];
    }

    public function messages()
    {
        return [
            'vehiculo_id.required' => 'El campo vehiculo_id es obligatorio.',
            'vehiculo_id.exists' => 'El vehiculo_id no existe en la base de datos.',
            'fecha_programada.required' => 'La fecha programada es obligatorio.',
            'fecha_programada.date' => 'La fecha programada debe ser una fecha válida.',
            'fecha_realizado.date' => 'El campo fecha_realizado debe ser una fecha válida.',
            'tipo_mantenimiento.required' => 'Debes colocar el tipo de mantenimiento.',

            'archivo.file' => 'El campo archivo debe ser un archivo.',
            'archivo.mimes' => 'El campo archivo debe ser un archivo de tipo: pdf, jpg, jpeg, png.',
            'archivo.max' => 'El tamaño máximo del archivo es de 10MB.',
            'kilometraje_actual.integer' => 'El kilometraje actual debe ser un número entero.',
            'kilometraje_actual.min' => 'El kilometraje actual no puede ser menor a 0.',
        ];
    }
}
