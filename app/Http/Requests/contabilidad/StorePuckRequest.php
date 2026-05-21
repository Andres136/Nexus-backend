<?php

namespace App\Http\Requests\contabilidad;

use Illuminate\Foundation\Http\FormRequest;

class StorePuckRequest extends FormRequest
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
// En tu StorePuckRequest.php


public function rules(): array
{
    return [
        // Ahora la regla 'unique' buscará "Puck cuenta123" en la BD
        'nombre' => 'required|unique:puck,nombre|string|max:255',
        'numero' => 'required|unique:puck,numero',
    ];
}

    public function messages()
    {
        return [
            'nombre.required' => 'El nombre del puck es obligatorio.',
            'nombre.unique' => 'El nombre del puck ya existe.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
            'numero.required' => 'El número del puck es obligatorio.',
            'numero.unique' => 'El número del puck ya existe.',
            'numero.integer' => 'El número debe ser un entero.',
            'numero.min' => 'El número debe ser al menos 1.',
        ];
    }   
}
