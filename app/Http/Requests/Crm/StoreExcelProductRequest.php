<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class StoreExcelProductRequest extends FormRequest
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
            'empresa_id' => 'required|exists:empresas,id',
            'bodega_id' => 'required|exists:bodegas,id',
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // Max 10MB
        ];
    }

    public function messages(): array
    {
        return [
            'empresa_id.required' => 'El campo empresa es obligatorio.',
            'empresa_id.exists' => 'La empresa seleccionada no existe.',
            'bodega_id.required' => 'El campo bodega es obligatorio.',
            'bodega_id.exists' => 'La bodega seleccionada no existe.',
            'file.required' => 'El archivo es obligatorio.',
            'file.file' => 'El archivo debe ser un archivo válido.',
            'file.mimes' => 'El archivo debe ser de tipo: xlsx, xls, csv.',
            'file.max' => 'El tamaño máximo del archivo es 10MB.',
        ];
    }
}
