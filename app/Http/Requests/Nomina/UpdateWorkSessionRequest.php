<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Campos principales — sometimes porque en update
            // no obligas a enviar todo, solo lo que cambió
            'users_id'           => 'sometimes|integer|exists:users,id',
            'kiosko_id'          => 'sometimes|integer|exists:kiosko_devices,id',
            'registro_diario'    => 'sometimes|date',
            'horario_laboral_id' => 'sometimes|integer|exists:jornada_laborals,id',

            // Horas — se actualizan cuando el kiosko marca
            'hora_entrada'         => 'sometimes|nullable|date_format:H:i:s',
            'hola_salida'          => 'sometimes|nullable|date_format:H:i:s|after:hora_entrada',
            'hora_salida_brake'    => 'sometimes|nullable|date_format:H:i:s',
            'horara_ingreso_brake' => 'sometimes|nullable|date_format:H:i:s',
            'hora_salida_almuerzo' => 'sometimes|nullable|date_format:H:i:s',
            'hora_ingreso_almuerzo'=> 'sometimes|nullable|date_format:H:i:s',

            // Minutos — el Service los recalcula al actualizar
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
            'users_id.exists'             => 'El empleado no existe',
            'kiosko_id.exists'            => 'El kiosko no existe',
            'registro_diario.date'        => 'La fecha no es válida',
            'horario_laboral_id.exists'   => 'La jornada laboral no existe',
            'hola_salida.after'           => 'La salida debe ser después de la entrada',
        ];
    }
}


