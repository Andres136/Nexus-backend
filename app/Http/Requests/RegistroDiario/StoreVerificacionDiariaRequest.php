<?php

namespace App\Http\Requests\RegistroDiario;

use Illuminate\Foundation\Http\FormRequest;

class StoreVerificacionDiariaRequest extends FormRequest
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
            'registro_diario_id' => 'required|exists:registro_diario,id',
            'pregunta_id' => 'required|exists:preguntas,id',
            'observaciones' => 'nullable|string|max:255',
            'estado' => 'required|string|in:si,no',

        ];
    }


    public function messages(): array
    {
        return [
            'registro_diario_id.required' => 'El campo registro diario es obligatorio.',
            'registro_diario_id.exists' => 'El registro diario seleccionado no existe.',
            'pregunta_id.required' => 'El campo pregunta es obligatorio. listo',
            'pregunta_id.exists' => 'La pregunta seleccionada no existe.',
            'observaciones.string' => 'El campo observaciones debe ser una cadena de texto.',
            'observaciones.max' => 'El campo observaciones no debe exceder los 255 caracteres.',
        ];
    }
}
