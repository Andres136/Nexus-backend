<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TareaRequest extends FormRequest
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
            'nombre' => 'required|string',
            'descripcion' => 'required|string',
            'fecha_fin' => 'required|date',
            'proceso_id' => 'required|integer',
            'user_id' => 'required|integer'
        ];
    }
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'proceso_id.required' => 'El proceso es obligatorio.',
            'user_id.required' => 'El usuario es obligatorio.',
        ];
    }
}
