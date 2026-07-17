<?php

namespace App\Http\Requests\Productividad;

use Illuminate\Foundation\Http\FormRequest;

class IniciarActividadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => 'required|in:TAREA,OTRA_ACTIVIDAD',
            // tarea_id queda opcional: hoy el título es texto libre y no
            // depende de que exista una Tarea formal previa.
            'tarea_id' => 'nullable|exists:tareas,id',
            'categoria_id' => 'required_if:tipo,OTRA_ACTIVIDAD|nullable|exists:categorias_actividad,id',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'El tipo de actividad es obligatorio.',
            'tipo.in' => 'El tipo de actividad debe ser tarea u otra actividad.',
            'tarea_id.exists' => 'La tarea seleccionada no existe.',
            'categoria_id.required_if' => 'Selecciona una categoría para la otra actividad.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'titulo.required' => 'Escribe qué vas a hacer.',
            'titulo.max' => 'El título no puede superar 255 caracteres.',
            'descripcion.max' => 'La descripción no puede superar 2000 caracteres.',
        ];
    }
}
