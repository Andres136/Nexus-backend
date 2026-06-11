<?php

namespace App\Http\Requests\contabilidad;

use Illuminate\Foundation\Http\FormRequest;

class ImportPuckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'El archivo de cuentas es obligatorio.',
            'file.mimes' => 'El archivo debe ser de tipo xlsx, xls o csv.',
            'file.max' => 'El archivo no puede superar los 10 MB.',
        ];
    }
}
