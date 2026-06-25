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
            'soportes'   => ['nullable', 'array'],
            'soportes.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip', 'max:10240'],
            'link'       => ['nullable', 'url', 'max:2048'],
            'cerrar'     => ['nullable', 'boolean'],
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
            'soportes.array'      => 'Los soportes deben enviarse como una lista de archivos.',
            'soportes.*.file'     => 'Cada soporte debe ser un archivo válido.',
            'soportes.*.mimes'    => 'Cada soporte debe ser jpg, png, pdf, documento, Excel, txt o zip.',
            'soportes.*.max'      => 'Cada soporte no puede superar 10 MB.',
            'link.url'            => 'El link debe ser una URL válida.',
            'link.max'            => 'El link no puede superar 2048 caracteres.',
        ];
    }
}
