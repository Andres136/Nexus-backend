<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StoreSolicitudPrestamoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto_solicitado' => 'required|numeric|min:1',
            'numero_cuotas_solicitadas' => 'required|integer|min:1|max:60',
            'frecuencia_pago_solicitada' => 'required|in:quincenal,mensual',
            'motivo' => 'nullable|string|max:1000',
        ];
    }
}
