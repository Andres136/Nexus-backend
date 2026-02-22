<?php

namespace App\Http\Requests\Tic;

use Illuminate\Foundation\Http\FormRequest;

class CambiarEstadoRequest extends FormRequest
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
           'estado' => 'required|string|in:pendiente,en_proceso,completado',

        'archivos' => 'nullable|array',
        'archivos.*' => 'file|mimes:png,jpg,jpeg,pdf,doc,docx|max:10240',

        'tipo_archivo' => 'nullable|string',
        'descripcion' => 'nullable|string',
        ];
    }
    public function withValidator($validator)
{
    $validator->after(function ($validator) {
        if ($this->estado === 'completado' && !$this->hasFile('archivos')) {
            $validator->errors()->add('archivos', 'Debe adjuntar al menos un archivo para completar el mantenimiento.');
        }
    });
}
    public function messages()
    {
        return [
            'estado.required' => 'El estado es obligatorio.',
            'estado.string' => 'El estado debe ser una cadena de texto.',
            'estado.in' => 'El estado debe ser uno de los siguientes: pendiente, en_proceso, completado.',
            'archivos.array' => 'Los archivos deben ser un arreglo.',
            'archivos.*.file' => 'Cada archivo debe ser un archivo válido.',
            'archivos.*.mimes' => 'Cada archivo debe ser de tipo png, jpg, jpeg, pdf, doc o docx.',
            'archivos.*.max' => 'Cada archivo no debe superar los 10MB.',
            'tipo_archivo.string' => 'El tipo de archivo debe ser una cadena de texto.',
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
        ];
    }
}
