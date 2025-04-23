<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class VehiculoRequest extends FormRequest
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
         // Obtener el ID del vehículo actual
        return [
            'placa' => 'required|string|max:10|unique:vehiculos,placa',
            'marca' => 'required|string|max:50',
            'modelo' => 'required|string|max:50',
            'anio' => 'required|integer|min:1900|max:' . date('Y'),
            'kilometraje_actual' => 'required|integer|min:0',
            'tipo' => 'required|string|max:50', // Cambiado de tipo_vehiculo a tipo
            'estado'=> 'required|in:Activo,En mantenimiento,Retirado',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240', // Tamaño máximo de 5MB
            'observaciones' => 'nullable|string|max:255',
            'licencia_transito' => 'required|string|max:50',
            'conductor' => 'nullable|string|max:50',
            //
        ];



    }

    public function messages()
    {
        return [
            'placa.required' => 'La placa es obligatoria.',
            'placa.unique' => 'La placa ya está registrada.',
            'marca.required' => 'La marca es obligatoria.',
            'modelo.required' => 'El modelo es obligatorio.',
            'anio.required' => 'El año es obligatorio.',
            'anio.integer' => 'El año debe ser un número entero.',
            'anio.min' => 'El año no puede ser menor a 1900.',
            'anio.max' => 'El año no puede ser mayor al año actual.',
            'tipo.required' => 'El tipo de vehículo es obligatorio.',
            'kilometraje_actual.required' => 'El kilometraje actual es obligatorio.',
             'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser uno de los siguientes: Activo, En mantenimiento, Retirado.',
            'foto.image' => 'El archivo debe ser una imagen.',
            'foto.mimes' => 'La imagen debe ser de tipo jpeg, png, jpg o gif.',
            'foto.max' => 'El tamaño máximo de la imagen es de 10MB.',
            'observaciones.string' => 'Las observaciones deben ser un texto.',
            'observaciones.max' => 'Las observaciones no pueden exceder los 255 caracteres.',
            'licencia_transito.required' => 'La licencia de tránsito es obligatoria.',
            'licencia_transito.string' => 'La licencia de tránsito debe ser un texto.',
            'conductor.string' => 'El conductor debe ser un texto.',
            'conductor.max' => 'El nombre del conductor no puede exceder los 50 caracteres.',
        ];
    }
}
