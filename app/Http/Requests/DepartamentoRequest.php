<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class DepartamentoRequest extends FormRequest
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
            'nombre' => 'required|string|max:100',
            'descripcion' => 'required|string',
            'macroprocesos_id' => 'required|exists:macroprocesos,id',
            'icono' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'

         
        ];
    }
    public function messages(): array
    {
        return [
          'nombre.required' => 'El nombre del departamento es requerido',
          'nombre.string' => 'El nombre del departamento debe ser un texto',
          'descripcion.required' => 'La descripción del departamento es requerida',
           'descripcion.string' => 'La descripción del departamento debe ser un texto',
            'macroprocesos_id.required' => 'El macroproceso es requerido',
            'icono.required' => 'La imagen del icono es requerida',
            'icono.max' => 'El ícono no debe exceder los 2 MB.',
        
      
        ];
    }
}
