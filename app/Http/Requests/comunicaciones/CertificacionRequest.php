<?php

namespace App\Http\Requests\comunicaciones;

use Illuminate\Foundation\Http\FormRequest;

class CertificacionRequest extends FormRequest
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
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'titulo' => [$required, 'string', 'max:255'],
            'subtitulo' => [$required, 'string', 'max:255'],
            'descripcion' => [$required, 'string'],
            'logo' => [$required, 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('activo')) {
            $this->merge([
                'activo' => filter_var($this->input('activo'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'El título es obligatorio.',
            'titulo.string' => 'El título debe ser una cadena de texto.',
            'titulo.max' => 'El título no puede exceder los 255 caracteres.',
            'subtitulo.required' => 'El subtítulo es obligatorio.',
            'subtitulo.string' => 'El subtítulo debe ser una cadena de texto.',
            'subtitulo.max' => 'El subtítulo no puede exceder los 255 caracteres.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'logo.required' => 'El logo es obligatorio.',
            'logo.image' => 'El logo debe ser una imagen.',
            'logo.mimes' => 'El logo debe ser un archivo jpg, jpeg, png, webp o svg.',
            'logo.max' => 'El logo no puede pesar más de 2 MB.',
        ];
    }
}
