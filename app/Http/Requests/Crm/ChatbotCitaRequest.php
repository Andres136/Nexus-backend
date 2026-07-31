<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class ChatbotCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_lead' => 'required|string|max:255',
            'email_lead' => 'nullable|email|max:255',
            'empresa_lead' => 'nullable|string|max:255',
            'telefono_lead' => 'nullable|string|max:20',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after:fecha_inicio',
            'titulo' => 'nullable|string|max:255',
            'notas' => 'nullable|string|max:2000',
            'estado' => 'nullable|in:pendiente,confirmada,cancelada,completada',
            'user_id' => 'nullable|integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_lead.required' => 'El nombre del contacto es obligatorio.',
            'email_lead.email' => 'El correo no es válido.',
            'fecha_inicio.required' => 'La fecha y hora de la cita son obligatorias.',
            'fecha_inicio.date' => 'La fecha de la cita no es válida.',
            'fecha_fin.after' => 'La hora de fin debe ser posterior a la de inicio.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'user_id.exists' => 'El ejecutivo seleccionado no existe.',
        ];
    }
}
