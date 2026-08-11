<?php

namespace App\Http\Requests\Hseq;

use Illuminate\Foundation\Http\FormRequest;

class CerrarHallazgoRequest extends FormRequest
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
            'foto' => 'required|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx|max:20480', // 20 MB
            'observaciones_cierre' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'foto.required' => 'Debes adjuntar una foto o archivo para cerrar el hallazgo.',
            'foto.file' => 'El archivo adjunto no es válido.',
            'foto.mimes' => 'El archivo debe ser jpg, jpeg, png, webp, pdf, doc, docx, xls o xlsx.',
            'foto.max' => 'El archivo no puede superar los 20 MB.',
            'observaciones_cierre.string' => 'Las observaciones deben ser texto.',
        ];
    }
}
