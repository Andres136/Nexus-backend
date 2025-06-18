<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class RequestFotoVehiculo extends FormRequest
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
            'fotos.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // Validación para cada foto
        ];
    }
    /**
     * Get the custom messages for the validation rules.
     */
    public function messages(): array
    {
        return [
            'fotos.*.required' => 'La foto es obligatoria.',
            'fotos.*.image' => 'El archivo debe ser una imagen.',
            'fotos.*.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg, gif.',
            'fotos.*.max' => 'La imagen no debe exceder los 5MB.',
        ];
    }
}
