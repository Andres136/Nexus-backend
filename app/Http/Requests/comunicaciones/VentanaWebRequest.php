<?php

namespace App\Http\Requests\comunicaciones;

use App\Models\comunicaciones\VentanaWeb;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VentanaWebRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['activo', 'boton_activo'] as $campo) {
            if ($this->has($campo)) {
                $this->merge([
                    $campo => filter_var($this->input($campo), FILTER_VALIDATE_BOOLEAN),
                ]);
            }
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $yaTieneImagen = VentanaWeb::query()->whereNotNull('imagen')->exists();

        return [
            'titulo' => ['required', 'string', 'max:255'],
            'subtitulo' => ['required', 'string', 'max:255'],
            'contenido' => ['required', 'string'],
            'imagen' => [
                Rule::requiredIf(! $yaTieneImagen),
                'image',
                'mimes:jpg,jpeg,png,webp,svg',
                'max:20480',
            ],
            'activo' => ['required', 'boolean'],
            'boton_activo' => ['required', 'boolean'],
            'boton_texto' => ['nullable', 'required_if:boton_activo,true', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'El título es obligatorio.',
            'subtitulo.required' => 'El subtítulo es obligatorio.',
            'contenido.required' => 'El contenido es obligatorio.',
            'imagen.required' => 'La imagen es obligatoria.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'La imagen debe ser jpg, jpeg, png, webp o svg.',
            'imagen.max' => 'La imagen no puede pesar más de 20 MB.',
            'activo.required' => 'Debe indicar si la ventana está activa.',
            'boton_activo.required' => 'Debe indicar si el botón está activo.',
            'boton_texto.required_if' => 'El texto del botón es obligatorio si el botón está activo.',
            'boton_texto.max' => 'El texto del botón no puede exceder los 50 caracteres.',
        ];
    }
}
