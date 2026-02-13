<?php

namespace App\Http\Requests\RegistroDiario;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegistroDiarioRequest extends FormRequest
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
            'departamento_id' => 'required|exists:departamentos,id',
            'pregunta_id' => 'required|exists:preguntas,id',
            'respuesta' => 'nullable|integer',
            'observaciones' => 'nullable|string',
            'tipo' => 'required|string|in:si,no',
            'novedad' => 'nullable|string',
        ];
    }


    public function messages(): array
    {
        return [
            'departamento_id.required' => 'El campo departamento es obligatorio.',
            'departamento_id.exists' => 'El departamento seleccionado no existe.',
            'pregunta_id.required' => 'El campo pregunta es obligatorio.',
            'pregunta_id.exists' => 'La pregunta seleccionada no existe.',
            'respuesta.required' => 'El campo respuesta es obligatorio.',
            'respuesta.integer' => 'El campo respuesta debe ser un número entero.',
            'respuesta.max' => 'El campo respuesta no debe exceder los 255 caracteres.',
            'observaciones.string' => 'El campo observaciones debe ser una cadena de texto.',
            'observaciones.max' => 'El campo observaciones no debe exceder los 255 caracteres.',
            'fecha.required' => 'El campo fecha es obligatorio.',
            'fecha.date' => 'El campo fecha debe ser una fecha válida.',
            'tipo.required' => 'El campo tipo es obligatorio.',
            'tipo.string' => 'El campo tipo debe ser una cadena de texto.',
            'tipo.in' => 'El campo tipo debe ser uno de los siguientes valores: tipo1, tipo2, tipo3.',
            'novedad.string' => 'El campo novedad debe ser una cadena de texto.',
        ];
    }
}
