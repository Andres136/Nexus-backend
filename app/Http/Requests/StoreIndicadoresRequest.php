<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIndicadoresRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }


        protected function prepareForValidation(): void
    {
        $this->merge([
            'departamento_id' => auth()->user()?->departamento_id,
            'user_id'         => auth()->id(),
        ]);
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
            'formula' => 'required|string|max:255',
            'meta' => 'required|numeric',
            'frecuencia' => 'required|in:Diario,Semanal,Quincenal,Mensual,Bimestral,Trimestral,Cuatrimestral,Semestral,Anual',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'user_id' => 'required|exists:users,id',
            'tipo_meta' => 'required|in:mayor,menor',
        ];
    }

    public function messages(): array
    {
        return [
            'departamento_id.required' => 'El campo departamento es obligatorio.',
            'departamento_id.exists' => 'El departamento seleccionado no existe.',
            'formula.required' => 'El campo fórmula es obligatorio.',
            'formula.string' => 'El campo fórmula debe ser una cadena de texto.',
            'formula.max' => 'El campo fórmula no debe exceder los 255 caracteres.',
            'meta.required' => 'El campo meta es obligatorio.',
            'meta.numeric' => 'El campo meta debe ser un número.',
            'frecuencia.required' => 'El campo frecuencia es obligatorio.',
            'frecuencia.in' => 'El campo frecuencia debe ser uno de los siguientes: Diario, Semanal, Quincenal, Mensual, Bimestral, Trimestral, Cuatrimestral, Semestral, Anual.',
            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.string' => 'El campo nombre debe ser una cadena de texto.',
            'nombre.max' => 'El campo nombre no debe exceder los 100 caracteres.',
            'descripcion.string' => 'El campo descripción debe ser una cadena de texto.',
            'descripcion.max' => 'El campo descripción no debe exceder los 255 caracteres.',
            'user_id.required' => 'El campo usuario es obligatorio.',
            'tipo_meta.required' => 'El campo tipo de meta es obligatorio.',
            'tipo_meta.in' => 'El campo tipo de meta debe ser "mayor" o "menor".',
          
        ];
    }
}
