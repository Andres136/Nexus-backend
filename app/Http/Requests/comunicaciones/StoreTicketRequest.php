<?php

namespace App\Http\Requests\comunicaciones;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
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
            'user_asignado_id'    => ['nullable', 'integer', 'exists:users,id'],
            'producto_id'         => ['nullable', 'integer', 'exists:products,id'],
            'departamento_id'     => ['nullable', 'integer', 'exists:departamentos,id'],
            'descripcion'         => ['required', 'string', 'max:5000'],
            'estado'              => ['nullable', 'in:pendiente,en_proceso,cerrado'],
            'archivo'             => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip', 'max:10240'],
            'prioridad'           => ['nullable', 'in:baja,media,alta'],
            'fecha_solucion'      => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'descripcion.required' => 'La descripción del ticket es obligatoria.',
            'descripcion.max'      => 'La descripción no puede superar 5000 caracteres.',
            'estado.in'            => 'El estado debe ser pendiente, en proceso o cerrado.',
            'prioridad.in'         => 'La prioridad debe ser baja, media o alta.',
            'archivo.file'         => 'El soporte debe ser un archivo válido.',
            'archivo.mimes'        => 'El soporte debe ser jpg, png, pdf, documento, Excel, txt o zip.',
            'archivo.max'          => 'El soporte no puede superar 10 MB.',
        ];
    }
}
