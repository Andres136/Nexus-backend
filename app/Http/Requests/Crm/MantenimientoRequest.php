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
            'fecha_programada' => 'required|date',
            'fecha_realizado' => 'required|date',
            'taller' => 'required|string|max:255',
            'descripcion_trabajo' => 'required|string|max:1000',
            'costo' => 'required|numeric|min:0',
            'kilometro_programado' => 'required|string|max:255',
            'tipo_mantenimiento' => 'required|string|max:255',
            'archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5000', // Validar el archivo
        ];
    }

    public function messages()
    {
        return [
            'vehiculo_id.required' => 'El campo vehiculo_id es obligatorio.',
            'vehiculo_id.exists' => 'El vehiculo_id no existe en la base de datos.',
            'fecha_programada.required' => 'La fecha programada es obligatorio.',
            'fecha_programada.date' => 'La fecha programada debe ser una fecha válida.',
            'fecha_realizado.required' => 'Debes colocar la fecha de realizado.',
            'fecha_realizado.date' => 'El campo fecha_realizado debe ser una fecha válida.',
            'taller.required' => 'El campo taller es obligatorio.',
            'descripcion_trabajo.required' => 'Debes colocar una descripcion del trabajo realizado.',
            'costo.required' => 'El campo costo es obligatorio.',
            'kilometro_programado.required' => 'Debes colocar el kilometraje programado.',
            'tipo_mantenimiento.required' => 'Debes colocar el tipo de mantenimiento.',
            'archivo.required' => 'Debes subir un soporte del mantenimiento.',
            'archivo.file' => 'El campo archivo debe ser un archivo.',
            'archivo.mimes' => 'El campo archivo debe ser un archivo de tipo: pdf, jpg, jpeg, png.',
            'archivo.max' => 'El campo archivo no debe ser mayor de 5MB.',
        ];
    }
}
