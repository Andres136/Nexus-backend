<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class ContratacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('contratacion');

        return [
            'id_contrato'         => 'required|integer|exists:tipo_contratos,id',
            'users_id'            => 'required|integer|exists:users,id',
            'no_salarial'         => 'required|numeric|min:0',
            'base_salario'        => 'required|numeric|min:0',
            'auxilio_transporte'  => 'nullable|numeric|min:0',
            'pago_frecuencia'     => 'required|integer',
            'inicio_contratacion' => 'required|date',
            'fin_contrato'        => 'nullable|date|after:inicio_contratacion',
            'status'              => 'boolean',
            'eps_id'              => 'required|integer|exists:seguridad_socials,id',
            'arl_id'              => 'required|integer|exists:seguridad_socials,id',
            'fondo_pensiones_id'  => 'required|integer|exists:seguridad_socials,id',
            'caja_pensiones_id'   => 'required|integer|exists:seguridad_socials,id',
        ];
    }

    public function messages(): array
    {
        return [
            'id_contrato.required'         => 'El tipo de contrato es obligatorio.',
            'id_contrato.exists'           => 'El tipo de contrato no existe.',
            'users_id.required'            => 'El usuario es obligatorio.',
            'users_id.exists'              => 'El usuario no existe.',
            'no_salarial.required'         => 'El componente no salarial es obligatorio.',
            'base_salario.required'        => 'El salario base es obligatorio.',
            'pago_frecuencia.required'     => 'La frecuencia de pago es obligatoria.',
            'inicio_contratacion.required' => 'La fecha de inicio es obligatoria.',
            'fin_contrato.after'           => 'La fecha de fin debe ser mayor a la de inicio.',
            'eps_id.required'              => 'La EPS es obligatoria.',
            'arl_id.required'              => 'La ARL es obligatoria.',
            'fondo_pensiones_id.required'  => 'El fondo de pensiones es obligatorio.',
            'caja_pensiones_id.required'   => 'La caja de compensación es obligatoria.',
        ];
    }
}