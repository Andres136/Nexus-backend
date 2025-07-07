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
           'anio' => 'required|integer|min:1900',
            'tipo' => 'required|string|max:50', // Cambiado de tipo_vehiculo a tipo
            'estado'=> 'required|in:Activo,En mantenimiento,Retirado',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240', // Tam
            'observaciones' => 'nullable|string|max:255',
            'licencia_transito' => 'required|string|max:50',
            'kilometraje_actual' => 'nullable|integer|min:0', // Permitir que el kilometraje actual sea opcional
            'conductor' => 'nullable|string|max:50',
            'nombre' => 'nullable|string|max:100', // Nuevo campo para el nombre del vehículo
            'tipo_servicio' => 'nullable|string|max:50', // Nuevo campo para el tipo de servicio
            'color' => 'nullable|string|max:30', // Nuevo campo para el color del vehículo
            'tipo_carroceria' => 'nullable|string|max:50', // Nuevo campo para el tipo de carrocería
            'tipo_combustible' => 'nullable|string|max:50', // Nuevo campo para el tipo de combustible
            'numero_motor' => 'nullable|string|max:50', // Nuevo campo para el número de motor
            'numero_chasis' => 'nullable|string|max:50', // Nuevo campo para el número de chasis
            'propietario' => 'nullable|string|max:100', // Nuevo campo para el propietario del vehículo
            'identificacion' => 'nullable|string|max:50', // Nuevo campo para la identificación del propietario
            'organismo_transito' => 'nullable|string|max:100', // Nuevo campo para el organismo de tránsito
            'fecha_matricula' => 'nullable|date_format:Y-m-d', 
            // Nuevo campo para la fecha de matrícula
            
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
            'kilometraje_actual.integer' => 'El campo kilometraje actual debe ser un número entero.',
            'kilometraje_actual.min' => 'El campo kilometraje actual no puede ser menor a 0.',
            'nombre.string' => 'El campo nombre debe ser una cadena de texto.',
            'nombre.max' => 'El campo nombre no puede tener más de 100 caracteres.',
            'tipo_servicio.string' => 'El campo tipo de servicio debe ser una cadena de texto.',
            'tipo_servicio.max' => 'El campo tipo de servicio no puede tener más than 50 characters.',
            'color.string' => 'El campo color debe ser una cadena de texto.',
            'color.max' => 'El campo color no puede tener más de 30 caracteres.',
            'tipo_carroceria.string' => 'El campo tipo de carrocería debe ser una cadena de texto.',
            'tipo_carroceria.max' => 'El campo tipo de carrocería no puede tener más de 50 caracteres.',
            'tipo_combustible.string' => 'El campo tipo de combustible debe ser una cadena de texto.',
            'tipo_combustible.max' => 'El campo tipo de combustible no puede tener más de 50 caracteres.',
            'numero_motor.string' => 'El campo número de motor debe ser una cadena de texto.',
            'numero_motor.max' => 'El campo número de motor no puede tener más de 50 caracteres.',
            'numero_chasis.string' => 'El campo número de chasis debe ser una cadena de texto.',
            'numero_chasis.max' => 'El campo número de chasis no puede tener más de 50 caracteres.',
            'propietario.string' => 'El campo propietario debe ser una cadena de texto.',
            'propietario.max' => 'El campo propietario no puede tener más de 100 caracteres.',
            'identificacion.string' => 'El campo identificación del propietario debe ser una cadena de texto.',
            'identificacion.max' => 'El campo identificación del propietario no puede tener más de 50 caracteres.',
            'organismo_transito.string' => 'El campo organismo de tránsito debe ser una cadena de texto.',
            'organismo_transito.max' => 'El campo organismo de tránsito no puede tener más de 100 caracteres.',
            'fecha_matricula.date_format' => 'El campo fecha de matrícula debe tener el formato Y-m-d.',
        ];
    } 
}
