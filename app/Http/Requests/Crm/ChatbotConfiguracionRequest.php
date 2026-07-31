<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class ChatbotConfiguracionRequest extends FormRequest
{
    /**
     * La ruta ya está protegida por el middleware `es_responsable_del_departamento`.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'avatar_url' => 'nullable|string|max:255',
            // `mimes` en vez de `image`: la regla `image` valida con getimagesize()
            // y a veces rechaza fotos válidas (metadata rara, formatos no rasterizados
            // que getimagesize no reconoce bien). `mimes` es más predecible.
            'avatar' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp|max:4096',
            'mensaje_bienvenida' => 'required|string|max:1000',
            'prompt_sistema' => 'required|string|max:8000',
            'sitio_web_url' => 'nullable|url:http,https|max:500',
            'departamento_id' => 'nullable|integer|exists:departamentos,id',
            'activo' => 'boolean',
            'openai_model' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del bot es obligatorio.',
            'mensaje_bienvenida.required' => 'El mensaje de bienvenida es obligatorio.',
            'mensaje_bienvenida.max' => 'El mensaje de bienvenida no puede superar los 1000 caracteres.',
            'prompt_sistema.required' => 'Las instrucciones de comportamiento son obligatorias.',
            'prompt_sistema.max' => 'Las instrucciones de comportamiento no pueden superar los 8000 caracteres.',
            'sitio_web_url.url' => 'Ingresa una dirección web válida que comience por http:// o https://.',
            'avatar.file' => 'El archivo de la foto no es válido.',
            'avatar.mimes' => 'La foto debe ser una imagen JPG, PNG, GIF o WEBP.',
            'avatar.max' => 'La foto no puede pesar más de 4 MB.',
            'departamento_id.exists' => 'El departamento seleccionado no existe.',
            'activo.boolean' => 'El campo "activo" no es válido.',
        ];
    }
}
