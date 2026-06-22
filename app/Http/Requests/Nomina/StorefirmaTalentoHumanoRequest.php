<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StorefirmaTalentoHumanoRequest extends FormRequest
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
             'firma' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'firma.required' => 'Debes seleccionar una imagen de firma.',
            'firma.image' => 'El archivo debe ser una imagen válida.',
            'firma.mimes' => 'La firma debe ser JPG, JPEG o PNG.',
        ];
    }
}
