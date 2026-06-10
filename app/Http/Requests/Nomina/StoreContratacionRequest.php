<?php

namespace App\Http\Requests\Nomina;

use App\Models\Nomina\Contratacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreContratacionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'id_contrato'          => 'required|integer|exists:tipo_contratos,id',
            'users_id'             => 'required|integer|exists:users,id',
            'empresa_id'           => 'required|integer|exists:empresas,id',
            'centro_costo'         => 'nullable|string|max:80',
            'tipo_documento'       => 'required|string|in:CC,CE,TI,PA,NIT',
            'numero_documento'     => 'required|string|max:20',
            'correo'                => 'nullable|email|max:255',
            'cargo'                => 'nullable|string|max:100',
            'no_salarial'          => 'required|numeric|min:0',
            'base_salario'         => 'required|numeric|min:0',
            'auxilio_transporte'   => 'nullable|numeric|min:0',
            'pago_frecuencia'      => 'required|integer',
            'inicio_contratacion'  => 'required|date',
            'fin_contrato'         => 'nullable|date|after:inicio_contratacion',
            'status'               => 'boolean',
            'eps_id'               => 'required|integer|exists:seguridad_socials,id',
            'arl_id'               => 'required|integer|exists:seguridad_socials,id',
            'fondo_pensiones_id'   => 'required|integer|exists:seguridad_socials,id',
            'caja_penciones_id'    => 'required|integer|exists:seguridad_socials,id',
        ];
    }

    public function messages(): array
    {
        return [
            'id_contrato.required'          => 'El tipo de contrato es obligatorio.',
            'id_contrato.exists'            => 'El tipo de contrato no existe.',
            'users_id.required'             => 'El empleado es obligatorio.',
            'users_id.exists'               => 'El empleado no existe.',
            'empresa_id.required'           => 'La empresa es obligatoria.',
            'empresa_id.exists'             => 'La empresa no existe.',
            'tipo_documento.required'       => 'El tipo de documento es obligatorio.',
            'tipo_documento.in'             => 'El tipo de documento debe ser CC, CE, TI, PA o NIT.',
            'numero_documento.required'     => 'El número de documento es obligatorio.',
            'numero_documento.max'          => 'El número de documento no puede superar 20 caracteres.',
            'correo.email'                   => 'El correo debe ser un correo electrónico válido.',
            'correo.max'                     => 'El correo no puede superar 255 caracteres.',
            'cargo.required'               => 'El cargo es obligatorio.',
            'cargo.max'                    => 'El cargo no puede superar 100 caracteres.',
            'no_salarial.required'          => 'El componente no salarial es obligatorio.',
            'base_salario.required'         => 'El salario base es obligatorio.',
            'pago_frecuencia.required'      => 'La frecuencia de pago es obligatoria.',
            'inicio_contratacion.required'  => 'La fecha de inicio es obligatoria.',
            'inicio_contratacion.date'      => 'La fecha de inicio no es válida.',
            'fin_contrato.date'             => 'La fecha de fin no es válida.',
            'fin_contrato.after'            => 'La fecha de fin debe ser posterior a la de inicio.',
            'eps_id.required'               => 'La EPS es obligatoria.',
            'eps_id.exists'                 => 'La EPS no existe.',
            'arl_id.required'               => 'La ARL es obligatoria.',
            'arl_id.exists'                 => 'La ARL no existe.',
            'fondo_pensiones_id.required'   => 'El fondo de pensiones es obligatorio.',
            'fondo_pensiones_id.exists'     => 'El fondo de pensiones no existe.',
            'caja_penciones_id.required'   => 'La caja de pensiones es obligatoria.',
            'caja_penciones_id.exists'     => 'La caja de pensiones no existe.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (!$this->filled('users_id')) {
                    return;
                }

                $tieneContratoActivo = Contratacion::where('users_id', $this->input('users_id'))
                    ->where('status', true)
                    ->exists();

                if ($tieneContratoActivo) {
                    $validator->errors()->add(
                        'users_id',
                        'Este empleado ya tiene un contrato activo. Debes inactivar o finalizar el contrato actual antes de registrar uno nuevo.'
                    );
                }
            },
        ];
    }
}
