<?php

namespace App\Http\Requests\comunicaciones;

use App\Support\DiasHabiles;
use Carbon\Carbon;
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
            'producto_id'         => ['nullable', 'integer', 'exists:products,id', 'required_if:tipo,soporte_equipo'],
            'departamento_id'     => ['nullable', 'integer', 'exists:departamentos,id'],
            'tipo'                => ['required', 'in:solicitud,soporte_equipo,desarrollo_nexus'],
            'descripcion'         => ['required', 'string', 'max:5000'],
            'estado'              => ['nullable', 'in:pendiente,en_proceso,cerrado'],
            'archivo'             => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip', 'max:10240'],
            'archivos'            => ['nullable', 'array'],
            'archivos.*'          => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip', 'max:10240'],
            'prioridad'           => ['nullable', 'in:baja,media,alta'],
            'fecha_entrega'       => ['required', 'date_format:Y-m-d', function ($attribute, $value, $fail) {
                $minimo = DiasHabiles::sumar(Carbon::now(), 3);
                if (Carbon::createFromFormat('Y-m-d', $value)->lt($minimo)) {
                    $fail("La fecha de entrega debe ser de al menos 3 días hábiles a partir de hoy ({$minimo->format('d/m/Y')} o después).");
                }
            }],
            'hora_entrega'        => ['required', 'date_format:H:i'],
            'fecha_solucion'      => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required'        => 'El tipo de ticket es obligatorio.',
            'tipo.in'              => 'El tipo debe ser solicitud, soporte de equipo o desarrollo Nexus.',
            'producto_id.required_if' => 'Selecciona el equipo asignado para un ticket de soporte de equipo.',
            'descripcion.required' => 'La descripción del ticket es obligatoria.',
            'descripcion.max'      => 'La descripción no puede superar 5000 caracteres.',
            'estado.in'            => 'El estado debe ser pendiente, en proceso o cerrado.',
            'prioridad.in'         => 'La prioridad debe ser baja, media o alta.',
            'fecha_entrega.required' => 'La fecha de entrega es obligatoria.',
            'fecha_entrega.date_format' => 'La fecha de entrega no es válida.',
            'hora_entrega.required' => 'La hora de entrega es obligatoria.',
            'hora_entrega.date_format' => 'La hora de entrega debe tener el formato HH:MM.',
            'archivo.file'         => 'El soporte debe ser un archivo válido.',
            'archivo.mimes'        => 'El soporte debe ser jpg, png, pdf, documento, Excel, txt o zip.',
            'archivo.max'          => 'El soporte no puede superar 10 MB.',
            'archivos.array'       => 'Los soportes deben enviarse como una lista de archivos.',
            'archivos.*.file'      => 'Cada soporte debe ser un archivo válido.',
            'archivos.*.mimes'     => 'Cada soporte debe ser jpg, png, pdf, documento, Excel, txt o zip.',
            'archivos.*.max'       => 'Cada soporte no puede superar 10 MB.',
        ];
    }
}
