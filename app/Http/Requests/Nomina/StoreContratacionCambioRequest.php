<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContratacionCambioRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'contratacion_id' => 'required|integer|exists:contrataciones,id',
            'tipo_cambio' => ['required', Rule::in([
                'cambio_tipo_contrato',
                'cambio_empresa',
                'cambio_cargo',
                'cambio_entidades',
                'cambio_condiciones_economicas',
                'cambio_fechas',
                'otro',
            ])],
            'fecha_cambio' => 'required|date',
            'motivo' => 'required|string|min:5|max:255',
            'observaciones' => 'nullable|string|max:2000',
            'datos_nuevos' => 'required|array|min:1',
            'datos_nuevos.id_contrato' => 'nullable|integer|exists:tipo_contratos,id',
            'datos_nuevos.empresa_id' => 'nullable|integer|exists:empresas,id',
            'datos_nuevos.cargo' => 'nullable|string|max:100',
            'datos_nuevos.base_salario' => 'nullable|numeric|min:0',
            'datos_nuevos.no_salarial' => 'nullable|numeric|min:0',
            'datos_nuevos.auxilio_transporte' => 'nullable|numeric|min:0',
            'datos_nuevos.pago_frecuencia' => 'nullable|integer',
            'datos_nuevos.inicio_contratacion' => 'nullable|date',
            'datos_nuevos.fin_contrato' => 'nullable|date',
            'datos_nuevos.eps_id' => ['nullable', 'integer', Rule::exists('seguridad_socials', 'id')->where('tipo', 'eps')->whereNull('deleted_at')],
            'datos_nuevos.arl_id' => ['nullable', 'integer', Rule::exists('seguridad_socials', 'id')->where('tipo', 'arl')->whereNull('deleted_at')],
            'datos_nuevos.fondo_pensiones_id' => ['nullable', 'integer', Rule::exists('seguridad_socials', 'id')->where('tipo', 'afp')->whereNull('deleted_at')],
            'datos_nuevos.caja_penciones_id' => ['nullable', 'integer', Rule::exists('seguridad_socials', 'id')->where('tipo', 'ccf')->whereNull('deleted_at')],
            'datos_nuevos.fondo_cesantias_id' => ['nullable', 'integer', Rule::exists('seguridad_socials', 'id')->where('tipo', 'cesantias')->whereNull('deleted_at')],
            'datos_nuevos.salario_integral' => 'nullable|boolean',
            'datos_nuevos.aplica_salud' => 'nullable|boolean',
            'datos_nuevos.aplica_pension' => 'nullable|boolean',
            'datos_nuevos.aplica_arl' => 'nullable|boolean',
            'datos_nuevos.aplica_sena' => 'nullable|boolean',
            'datos_nuevos.aplica_icbf' => 'nullable|boolean',
            'datos_nuevos.aplica_caja_compensacion' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'contratacion_id.required' => 'Debes seleccionar un contrato.',
            'tipo_cambio.required' => 'El tipo de cambio es obligatorio.',
            'fecha_cambio.required' => 'La fecha de cambio es obligatoria.',
            'motivo.required' => 'El motivo es obligatorio.',
            'datos_nuevos.required' => 'Debes indicar al menos un dato nuevo del contrato.',
        ];
    }
}
