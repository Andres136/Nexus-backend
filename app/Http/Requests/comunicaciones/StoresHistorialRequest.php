<?php

namespace App\Http\Requests\comunicaciones;

use Illuminate\Foundation\Http\FormRequest;

class StoresHistorialRequest extends FormRequest
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
            'comentario' => ['required', 'string', 'max:5000'],
            'soporte'    => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'comentario.required' => 'El comentario es obligatorio.',
            'comentario.max'      => 'El comentario no puede superar 5000 caracteres.',
            'soporte.file'        => 'El soporte debe ser un archivo válido.',
            'soporte.mimes'       => 'El soporte debe ser jpg, png, pdf, documento, Excel, txt o zip.',
            'soporte.max'         => 'El soporte no puede superar 10 MB.',
        ];
    }
}
