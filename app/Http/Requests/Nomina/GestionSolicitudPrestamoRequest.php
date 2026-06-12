<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class GestionSolicitudPrestamoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto_aprobado' => 'nullable|numeric|min:1',
            'tasa_interes_porcentaje' => 'nullable|numeric|min:0|max:100',
            'numero_cuotas_aprobadas' => 'required|integer|min:1|max:60',
            'frecuencia_pago_aprobada' => 'required|in:quincenal,mensual',
            'inicio_descuento' => 'required|date',
            'observacion_nomina' => 'nullable|string|max:1000',
        ];
    }
}
