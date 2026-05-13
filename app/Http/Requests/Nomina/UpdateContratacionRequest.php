<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContratacionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'id_contrato'          => 'sometimes|integer|exists:tipo_contratos,id',
            'users_id'             => 'sometimes|integer|exists:users,id',
            'empresa_id'           => 'sometimes|integer|exists:empresas,id',
            'no_salarial'          => 'sometimes|numeric|min:0',
            'base_salario'         => 'sometimes|numeric|min:0',
            'auxilio_transporte'   => 'nullable|numeric|min:0',
            'pago_frecuencia'      => 'sometimes|integer',
            'inicio_contratacion'  => 'sometimes|date',
            'fin_contrato'         => 'nullable|date|after_or_equal:inicio_contratacion',
            'status'               => 'sometimes|boolean',
            'eps_id'               => 'sometimes|integer|exists:seguridad_socials,id',
            'arl_id'               => 'sometimes|integer|exists:seguridad_socials,id',
            'fondo_pensiones_id'   => 'sometimes|integer|exists:seguridad_socials,id',
            'caja_compensacion_id' => 'sometimes|integer|exists:seguridad_socials,id',
        ];
    }

    public function messages(): array
    {
        return [
            'id_contrato.exists'            => 'El tipo de contrato no existe.',
            'users_id.exists'               => 'El empleado no existe.',
            'empresa_id.exists'             => 'La empresa no existe.',
            'inicio_contratacion.date'      => 'La fecha de inicio no es válida.',
            'fin_contrato.date'             => 'La fecha de fin no es válida.',
            'fin_contrato.after_or_equal'   => 'La fecha de fin debe ser igual o posterior a la de inicio.',
            'eps_id.exists'                 => 'La EPS no existe.',
            'arl_id.exists'                 => 'La ARL no existe.',
            'fondo_pensiones_id.exists'     => 'El fondo de pensiones no existe.',
            'caja_compensacion_id.exists'   => 'La caja de compensación no existe.',
        ];
    }
}
