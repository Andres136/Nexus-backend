<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNominaParametroLaboralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $uuid = $this->route('parametros_laborale');

        return [
            'anio' => ['required', 'integer', 'min:2000', 'max:2100'],
            'fecha_vigencia' => [
                'required',
                'date',
                Rule::unique('nomina_parametros_laborales', 'fecha_vigencia')
                    ->where(fn ($query) => $query->where('anio', $this->input('anio')))
                    ->ignore($uuid, 'uuid'),
            ],
            'salario_minimo' => ['required', 'numeric', 'min:0'],
            'auxilio_transporte' => ['nullable', 'numeric', 'min:0'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}
