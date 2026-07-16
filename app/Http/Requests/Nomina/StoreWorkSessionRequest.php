<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ✅ cambiado de false a true
    }

    public function rules(): array
    {
        return [
            // Obligatorios
            'user_id'           => 'required|integer|exists:users,id',
            'kiosko_id'          => 'required|integer|exists:kiosko_devices,id',
            'registro_diario'    => 'required|date',
            'horario_laboral_id' => 'nullable|integer|exists:jornada_laborals,id',

            // Horas — nullable porque se llenan progresivamente
            'hora_entrada'         => 'nullable|date_format:H:i:s',
            // Foto de respaldo del kiosko cuando el reconocimiento facial falla
            // y el empleado marca con cédula (data URL base64, ej. data:image/jpeg;base64,...)
            'foto_respaldo'        => 'nullable|string',
            'hora_salida'          => 'nullable|date_format:H:i:s|after:hora_entrada',
            'hora_salida_brake'    => 'nullable|date_format:H:i:s',
            'hora_ingreso_brake' => 'nullable|date_format:H:i:s',
            'hora_salida_almuerzo' => 'nullable|date_format:H:i:s',
            'hora_ingreso_almuerzo'=> 'nullable|date_format:H:i:s',

            // Minutos — los calcula el Service
            'minutos_trabajados' => 'sometimes|integer|min:0',
            'minutos_pausa'      => 'sometimes|integer|min:0',
            'minutos_tardanza'   => 'sometimes|integer|min:0',
            'sabado_minutos'     => 'sometimes|numeric|min:0',
            'festivo_minutos'    => 'sometimes|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'users_id.required'           => 'El empleado es obligatorio',
            'users_id.exists'             => 'El empleado no existe',
            'kiosko_id.required'          => 'El kiosko es obligatorio',
            'kiosko_id.exists'            => 'El kiosko no existe',
            'registro_diario.required'    => 'La fecha del registro es obligatoria',
            'registro_diario.date'        => 'La fecha no es válida',
            'horario_laboral_id.exists'   => 'La jornada laboral no existe',
            'hora_salida.after'           => 'La salida debe ser después de la entrada',
        ];
    }
}
