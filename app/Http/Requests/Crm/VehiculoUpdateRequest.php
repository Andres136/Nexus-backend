<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class VehiculoUpdateRequest extends FormRequest
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
        $id = $this->vehiculo->id; // Obtener el ID del vehículo actual
        return [
            'placa' => 'required|string|max:10|unique:vehiculos,placa,' . $id,
            'marca' => 'required|string|max:50',
            'modelo' => 'required|string|max:50',
            'anio' => 'required|integer|min:1900|max:' . date('Y'),
            'tipo' => 'required|string|max:50', // Cambiado de tipo_vehiculo a tipo
            'estado'=> 'required|in:Activo,En mantenimiento,Retirado',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240', // Tam
            'observaciones' => 'nullable|string|max:255',
            'licencia_transito' => 'required|string|max:50',
            'conductor' => 'nullable|string|max:50',
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
             'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser uno de los siguientes: Activo, En mantenimiento, Retirado.',
            'foto.image' => 'El archivo debe ser una imagen.',
            'foto.mimes' => 'La imagen debe ser de tipo jpeg, png, jpg o gif.',
            'foto.max' => 'El tamaño máximo de la imagen es de 10MB.',
            'observaciones.string' => 'El campo observaciones debe ser una cadena de texto.',
            'observaciones.max' => 'El campo observaciones no puede tener más de 255 caracteres.',
            'licencia_transito.required' => 'La licencia de tránsito es obligatoria.',
            'licencia_transito.string' => 'El campo licencia de tránsito debe ser una cadena de texto.',
            'licencia_transito.max' => 'El campo licencia de tránsito no puede tener más de 50 caracteres.',
            'conductor.string' => 'El campo conductor debe ser una cadena de texto.',
            'conductor.max' => 'El campo conductor no puede tener más de 50 caracteres.',
            'conductor.required' => 'El campo conductor es obligatorio.',
        ];
    } 
}
