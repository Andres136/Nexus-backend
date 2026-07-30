<?php

namespace App\Http\Requests\comunicaciones;

use Illuminate\Foundation\Http\FormRequest;

class StorePublicacionMarketingRequest extends FormRequest
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
            'titulo' => 'required|string|max:150',
            'red_social_id' => 'required|exists:redes_sociales,id',
            'tipo_post_id' => 'required|exists:tipos_post,id',
            'fecha' => 'required|date',
            'estado' => 'nullable|in:programado,publicado,cancelado',
            'link' => 'nullable|url|max:2048',
            'descripcion' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'titulo.required' => 'El título es obligatorio.',
            'red_social_id.required' => 'La red social es obligatoria.',
            'red_social_id.exists' => 'La red social seleccionada no existe.',
            'tipo_post_id.required' => 'El tipo de post es obligatorio.',
            'tipo_post_id.exists' => 'El tipo de post seleccionado no existe.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha debe ser una fecha válida.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'link.url' => 'El link debe ser una URL válida.',
        ];
    }
}
