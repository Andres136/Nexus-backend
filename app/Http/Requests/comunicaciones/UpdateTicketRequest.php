<?php

namespace App\Http\Requests\comunicaciones;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_asignado_id' => ['nullable', 'integer', 'exists:users,id'],
            'producto_id'      => ['nullable', 'integer', 'exists:products,id'],
            'departamento_id'  => ['nullable', 'integer', 'exists:departamentos,id'],
            'descripcion'      => ['sometimes', 'required', 'string', 'max:5000'],
            'estado'           => ['sometimes', 'in:pendiente,en_proceso,cerrado'],
            'archivo'          => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip', 'max:10240'],
            'prioridad'        => ['sometimes', 'in:baja,media,alta'],
            'fecha_solucion'   => ['nullable', 'date'],
            'comentario'       => ['nullable', 'string', 'max:5000'],
        ];
    }
}
