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
            'conductor' => 'required|string|max:50',
        'anio' => 'required|integer|min:1900',

            'kilometraje_actual' => 'required|integer|min:0',
            'tipo' => 'required|string|max:50', // Cambiado de tipo_vehiculo a tipo
            'estado'=> 'required|in:Activo,En mantenimiento,Retirado',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240', // Tamaño máximo de 5MB
            'observaciones' => 'nullable|string|max:255',
            'licencia_transito' => 'required|string|max:50',
            'conductor' => 'required|string|max:50',
            'nombre' => 'required|string|max:100', // Nuevo campo para el nombre del vehículo
            'tipo_servicio' => 'required|string|max:50', // Nuevo campo para el tipo de servicio
            'color' => 'required|string|max:30', // Nuevo campo para el color del vehículo
            'tipo_carroceria' => 'required|string|max:50', // Nuevo campo para el tipo de carrocería
            'tipo_combustible' => 'required|string|max:50', // Nuevo campo para el tipo de combustible
            'numero_motor' => 'required|string|max:50', // Nuevo campo para el número de motor
            'numero_chasis' => 'required|string|max:50', // Nuevo campo para el número de chasis
            'propietario' => 'required|string|max:100', // Nuevo campo para el propietario del vehículo
            'identificacion' => 'required|string|max:50', // Nuevo campo para la identificación del propietario
            'organismo_transito' => 'required|string|max:100', // Nuevo campo para el organismo de tránsito
            'fecha_matricula' => 'required|date_format:Y-m-d', // Nuevo campo para la fecha de matrícula

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
            'licencia_transito.max' => 'La licencia de tránsito no puede exceder los 50 caracteres.',
            'conductor.required' => 'El conductor es obligatorio.',
            'conductor.string' => 'El conductor debe ser un texto.',
            'conductor.max' => 'El nombre del conductor no puede exceder los 50 caracteres.',
            'nombre.required' => 'El nombre del vehículo es obligatorio.',
            'nombre.string' => 'El nombre del vehículo debe ser un texto.',
            'nombre.max' => 'El nombre del vehículo no puede exceder los 100 caracteres.',
            'tipo_servicio.required' => 'El tipo de servicio es obligatorio.',
            'tipo_servicio.string' => 'El tipo de servicio debe ser un texto.',
            'tipo_servicio.max' => 'El tipo de servicio no puede exceder los 50 caracteres.',
            'color.required' => 'El color del vehículo es obligatorio.',
            'color.string' => 'El color del vehículo debe ser un texto.',

            'color.max' => 'El color del vehículo no puede exceder los 30 caracteres.',
            'tipo_carroceria.required' => 'El tipo de carrocería es obligatorio.',
            'tipo_carroceria.string' => 'El tipo de carrocería debe ser un texto.',
            
            'tipo_carroceria.max' => 'El tipo de carrocería no puede exceed 50 characters.',
            'tipo_combustible.required' => 'El tipo de combustible es obligatorio.',
            'tipo_combustible.string' => 'El tipo de combustible debe ser un texto.',
            'tipo_combustible.max' => 'El tipo de combustible no puede exceder los 50 caracteres.',
            'numero_motor.required' => 'El número de motor es obligatorio.',
            'numero_motor.string' => 'El número de motor debe ser un texto.',
            'numero_motor.max' => 'El número de motor no puede exceder los 50 caracteres.',
            'numero_chasis.required' => 'El número de chasis es obligatorio.',
            'numero_chasis.string' => 'El número de chasis debe ser un texto.',
            'numero_chasis.max' => 'El número de chasis no puede exceder los 50 caracteres.',
            'propietario.required' => 'El propietario es obligatorio.',
            'propietario.string' => 'El propietario debe ser un texto.',
            'propietario.max' => 'El nombre del propietario no puede exceder los 100 caracteres.',
            'identificacion.required' => 'La identificación del propietario es obligatoria.',
            'identificacion.string' => 'La identificación del propietario debe ser un texto.',
            'identificacion.max' => 'La identificación del propietario no puede exceder los 50 caracteres.',
            'organismo_transito.required' => 'El organismo de tránsito es obligatorio.',
            'organismo_transito.string' => 'El organismo de tránsito debe ser un texto.',
            'organismo_transito.max' => 'El organismo de tránsito no puede exceder los 100 caracteres.',
            'fecha_matricula.required' => 'La fecha de matrícula es obligatoria.',
            'fecha_matricula.date_format' => 'La fecha de matrícula debe tener el formato Y-m-d.',
            'fecha_matricula.nullable' => 'La fecha de matrícula es opcional.',

        ];
    }
}
