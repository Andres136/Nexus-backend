<?php

namespace App\Http\Requests\Hseq;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNormaIsoRequest extends FormRequest
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
        $id = collect($this->route()->parameters())->first();

        return [
            'nombre' => ['required', 'string', 'max:255', Rule::unique('normas_iso', 'nombre')->ignore($id)],
            'activa' => 'nullable|boolean',
        ];
    }

    public function messages()
    {
        return [
            'nombre.required' => 'El nombre de la norma es obligatorio.',
            'nombre.string' => 'El nombre de la norma debe ser una cadena de texto.',
            'nombre.max' => 'El nombre de la norma no puede exceder los 255 caracteres.',
            'nombre.unique' => 'Ya existe una norma ISO con ese nombre.',
            'activa.boolean' => 'El campo activa debe ser verdadero o falso.',
        ];
    }
}
