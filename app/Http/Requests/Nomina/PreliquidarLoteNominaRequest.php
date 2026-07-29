<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class PreliquidarLoteNominaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periodo_inicio'     => 'required|date',
            'periodo_fin'        => 'required|date|after_or_equal:periodo_inicio',
            'jornada_laboral_id' => 'required|integer|exists:jornada_laborals,id',
            'sede_id'            => 'nullable|integer|exists:sedes,id',
            'empresa_id'         => 'nullable|integer|exists:empresas,id',
            'descontar_tardanzas' => 'nullable|boolean',
            'excluir_tardanza_ids' => 'nullable|array',
            'excluir_tardanza_ids.*' => 'integer',
            'excluir_permiso_ids' => 'nullable|array',
            'excluir_permiso_ids.*' => 'integer',
            'decisiones_permisos' => 'nullable|array',
            'decisiones_permisos.*.user_id' => 'required|integer|exists:users,id',
            'decisiones_permisos.*.permisos_descontar_ids' => 'present|array',
            'decisiones_permisos.*.permisos_descontar_ids.*' => 'integer|exists:permisos,id',
        ];
    }

    public function messages(): array
    {
        return [
            'periodo_inicio.required'     => 'La fecha de inicio del período es obligatoria.',
            'periodo_inicio.date'         => 'La fecha de inicio debe ser una fecha válida.',
            'periodo_fin.required'        => 'La fecha de fin del período es obligatoria.',
            'periodo_fin.date'            => 'La fecha de fin debe ser una fecha válida.',
            'periodo_fin.after_or_equal'  => 'La fecha de fin debe ser igual o posterior al inicio.',
            'jornada_laboral_id.required' => 'La jornada laboral es obligatoria.',
            'jornada_laboral_id.exists'   => 'La jornada laboral no existe.',
            'sede_id.exists'              => 'La sede no existe.',
            'empresa_id.exists'           => 'La empresa no existe.',
        ];
    }
}
