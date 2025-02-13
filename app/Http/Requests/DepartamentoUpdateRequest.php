<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepartamentoUpdateRequest extends FormRequest
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
    public function rules()
    { return [
        'nombre'           => 'required|string|max:255',
        'descripcion'      => 'required|string',
        'macroprocesos_id' => 'required|exists:macroprocesos,id',
        // Para update, el icono no es obligatorio, solo se validará si se envía uno
        'icono'            => 'nullable|image'
    ];



    }
    public function messages()
    {
        return [
            'nombre.required'           => 'El nombre es obligatorio',
            'nombre.string'             => 'El nombre debe ser una cadena de texto',
            'nombre.max'                => 'El nombre no puede tener más de 255 caracteres',
            'descripcion.required'      => 'La descripción es obligatoria',
            'descripcion.string'        => 'La descripción debe ser una cadena de texto',
            'macroprocesos_id.required' => 'El macroproceso es obligatorio',
            'macroprocesos_id.exists'   => 'El macroproceso no existe',
            'icono.image'               => 'El icono debe ser una imagen'
        ];
    }
    

}
