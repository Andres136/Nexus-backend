<?php

namespace App\Http\Requests\comunicaciones;

use Illuminate\Foundation\Http\FormRequest;

class PlantillaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'            => 'required|string|max:255',
            'tipo'              => 'nullable|string|max:100',
            'contenido_html'    => 'nullable|string',
            'video_url'         => 'nullable|url',

            'imagenes_keep'     => 'nullable',
            'logos_keep'        => 'nullable',

            'imagenes'          => 'nullable|array',
            'imagenes.*'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240',

            'logos_empresas'    => 'nullable|array',
            'logos_empresas.*'  => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240',

            'imagen_principal'  => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240',

            'certificaciones'           => 'nullable|array',
            'certificaciones.*.nombre'  => 'nullable|string|max:255',
            'certificaciones.*.logo'    => 'nullable|file|mimes:jpg,jpeg,png,webp,gif|max:10240',
            'certificaciones.*.url_cert'=> 'nullable|string|max:255',

            'redes_sociales'            => 'nullable|array',
            'redes_sociales.*.nombre'   => 'nullable|string|max:255',
            'redes_sociales.*.url'      => 'nullable|string|max:255',

            'descargas'                 => 'nullable|array',
            'descargas.*.nombre'        => 'nullable|string|max:255',
            'descargas.*.link'          => 'nullable|string|max:255',

            'publicada'         => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'           => 'El nombre de la plantilla es obligatorio.',
            'nombre.string'             => 'El nombre de la plantilla debe ser una cadena de texto.',
            'nombre.max'                => 'El nombre de la plantilla no debe exceder los 255 caracteres.',
            'tipo.string'               => 'El tipo de plantilla debe ser una cadena de texto.',
            'tipo.max'                  => 'El tipo no debe exceder los 100 caracteres.',
            'video_url.url'             => 'La URL del video no tiene un formato válido.',
            'video_url.max'             => 'La URL del video no debe exceder los 255 caracteres.',
            'imagenes.array'            => 'Las imágenes deben ser un arreglo.',
            'imagenes.*.image'          => 'Cada imagen debe ser un archivo de imagen válido.',
            'imagenes.*.mimes'          => 'Las imágenes deben ser de tipo jpeg, png, jpg, gif o svg.',
            'imagenes.*.max'            => 'Cada imagen no debe superar los 10 MB.',
            'logos_empresas.array'      => 'Los logos de empresas deben ser un arreglo.',
            'logos_empresas.*.image'    => 'Cada logo debe ser un archivo de imagen válido.',
            'logos_empresas.*.mimes'    => 'Los logos deben ser de tipo jpeg, png, jpg, gif o svg.',
            'logos_empresas.*.max'      => 'Cada logo no debe superar los 10 MB.',
            'imagen_principal.image'    => 'La imagen principal debe ser un archivo de imagen válido.',
            'imagen_principal.mimes'    => 'La imagen principal debe ser de tipo jpeg, png, jpg, gif o svg.',
            'imagen_principal.max'      => 'La imagen principal no debe superar los 10 MB.',
            'certificaciones.array'     => 'Las certificaciones deben ser un arreglo.',
            'redes_sociales.array'      => 'Las redes sociales deben ser un arreglo.',
            'descargas.array'           => 'Las descargas deben ser un arreglo.',
            'publicada.boolean'         => 'El campo publicada debe ser verdadero o falso.',
        ];
    }
}
