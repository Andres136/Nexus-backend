<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StoreConvocatoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:2000',
            'imagen' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
            'sede_id' => 'nullable|exists:sedes,id',
            'activa' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'Debes indicar el título de la convocatoria.',
            'imagen.required' => 'Debes seleccionar una imagen para la convocatoria.',
            'imagen.image' => 'El archivo debe ser una imagen válida.',
            'imagen.mimes' => 'La imagen debe ser JPG, JPEG, PNG o WEBP.',
            'imagen.max' => 'La imagen no debe superar los 10 MB.',
            'sede_id.exists' => 'La sede seleccionada no es válida.',
        ];
    }
}
