<?php

namespace App\Http\Requests\comunicaciones;

use Illuminate\Foundation\Http\FormRequest;

class PlantillaRequest extends FormRequest
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
        'nombre' => 'required|string|max:255',
        'imagen_principal' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif|max:10240',//10 MB
        'tipo' => 'nullable|string|max:255',
        'contenido_html' => 'nullable|string',
        'video_url' => 'nullable|string|max:255',

        //  Archivos múltiples
        'imagenes' => 'nullable|array',
        'imagenes.*' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif|max:10240',//10 MB

        'logos_empresas' => 'nullable|array',
        'logos_empresas.*' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif|max:10240',//10 MB

        //  Certificaciones anidadas
        'certificaciones' => 'nullable|array',
        'certificaciones.*.nombre' => 'nullable|string|max:255',
        'certificaciones.*.logo' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif|max:10240',//10 MB
        'certificaciones.*.url_cert' => 'nullable|string|max:255',

        //  Redes sociales
        'redes_sociales' => 'nullable|array',
        'redes_sociales.*.nombre' => 'nullable|string|max:255',
        'redes_sociales.*.url' => 'nullable|string|max:255',
  
        //  Archivos descargables
        'descargas' => 'nullable|array',
        'descargas.*.nombre' => 'nullable|string|max:255',
        'descargas.*.link' => 'nullable|string|max:255',

        'publicada' => 'boolean',
    ];
}

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la plantilla es obligatorio.',
            'nombre.string' => 'El nombre de la plantilla debe ser una cadena de texto.',
            'nombre.max' => 'El nombre de la plantilla no debe exceder los 255 caracteres.',
            'tipo.string' => 'El tipo de plantilla debe ser una cadena de texto.',
            'tipo.max' => 'El tipo de plantilla no debe exceder los 255 caracteres.',
            'video_url.string' => 'La URL del video debe ser una cadena de texto.',
            'video_url.max' => 'La URL del video no debe exceder los 255 caracteres.',
            'imagenes.array' => 'Las imágenes deben ser un arreglo.',
            'logos_empresas.array' => 'Los logos de empresas deben ser un arreglo.',
            'certificaciones.array' => 'Las certificaciones deben ser un arreglo.',
            'redes_sociales.array' => 'Las redes sociales deben ser un arreglo.',
            'descargas.array' => 'Las descargas deben ser un arreglo.',
            'publicada.boolean' => 'El campo publicada debe ser verdadero o falso.',    
        ];
    }
}
