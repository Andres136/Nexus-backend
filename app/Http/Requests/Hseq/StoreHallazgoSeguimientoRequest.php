<?php

namespace App\Http\Requests\Hseq;

use Illuminate\Foundation\Http\FormRequest;

class StoreHallazgoSeguimientoRequest extends FormRequest
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
            'hallazgo_id' => 'required|integer|exists:hallazgo_novedades,id',
            'observacion' => 'required|string|max:255',
        ];
    }

    public  function messages()
    {
        return [
            'hallazgo_id.required' => 'El campo hallazgo_id es obligatorio.',
            'hallazgo_id.integer' => 'El campo hallazgo_id debe ser un número entero.',
            'hallazgo_id.exists' => 'El hallazgo_id proporcionado no existe en la base de datos.',
            'observacion.required' => 'El campo observacion es obligatorio.',
            'observacion.string' => 'El campo observacion debe ser una cadena de texto.',
            'observacion.max' => 'El campo observacion no puede exceder los 255 caracteres.',
        ];
    }
}
