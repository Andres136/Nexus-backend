<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContratacionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'id_contrato'         => 'sometimes|integer|exists:tipo_contratos,id',
            'users_id'            => 'sometimes|integer|exists:users,id',
            'no_salarial'         => 'sometimes|numeric|min:0',
            'base_salario'        => 'sometimes|numeric|min:0',
            'auxilio_transporte'  => 'nullable|numeric|min:0',
            'pago_frecuencia'     => 'sometimes|integer',
            'inicio_contratacion' => 'sometimes|date',
            'fin_contrato'        => 'nullable|date|after:inicio_contratacion',
            'status'              => 'boolean',
            'eps_id'              => 'sometimes|integer|exists:seguridad_socials,id',
            'arl_id'              => 'sometimes|integer|exists:seguridad_socials,id',
            'fondo_pensiones_id'  => 'sometimes|integer|exists:seguridad_socials,id',
            'caja_pensiones_id'   => 'sometimes|integer|exists:seguridad_socials,id',
        ];
    }

    public function messages(): array
    {
        return [
            'id_contrato.exists'           => 'El tipo de contrato no existe.',
            'users_id.exists'              => 'El usuario no existe.',
            'fin_contrato.after'           => 'La fecha de fin debe ser mayor a la de inicio.',
            'eps_id.exists'                => 'La EPS no existe.',
            'arl_id.exists'                => 'La ARL no existe.',
            'fondo_pensiones_id.exists'    => 'El fondo de pensiones no existe.',
            'caja_pensiones_id.exists'     => 'La caja de compensación no existe.',
        ];
    }
}
