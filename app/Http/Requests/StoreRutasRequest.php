<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRutasRequest extends FormRequest
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
            'rutas' => 'required|array|min:1',
            'rutas.*.path' => 'required|string|max:255',
            'rutas.*.name' => 'nullable|string|max:255',
            'rutas.*.module' => 'nullable|string|max:255',
            'rutas.*.enabled' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'rutas.required' => 'Debes enviar al menos una ruta.',
            'rutas.array' => 'Formato inválido. Se esperaba un arreglo de rutas.',
            'rutas.min' => 'Debes registrar al menos una ruta.',

            'rutas.*.path.required' => 'El campo ruta es obligatorio.',
            'rutas.*.path.string' => 'El campo ruta debe ser una cadena de texto.',
            'rutas.*.path.max' => 'El campo ruta no debe exceder los 255 caracteres.',

            'rutas.*.name.string' => 'El campo nombre debe ser una cadena de texto.',
            'rutas.*.name.max' => 'El campo nombre no debe exceder los 255 caracteres.',

            'rutas.*.module.string' => 'El campo módulo debe ser una cadena de texto.',
            'rutas.*.module.max' => 'El campo módulo no debe exceder los 255 caracteres.',

            'rutas.*.enabled.boolean' => 'El campo habilitado debe ser verdadero o falso.',
        ];
    }
}
