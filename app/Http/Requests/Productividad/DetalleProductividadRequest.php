<?php

namespace App\Http\Requests\Productividad;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class DetalleProductividadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('fecha_inicio') && $this->filled('fecha')) {
            $this->merge([
                'fecha_inicio' => $this->query('fecha'),
                'fecha_fin' => $this->query('fecha'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $inicio = Carbon::parse($this->input('fecha_inicio'))->startOfDay();
            $fin = Carbon::parse($this->input('fecha_fin'))->startOfDay();

            if ($inicio->diffInDays($fin) > 365) {
                $validator->errors()->add('fecha_fin', 'El rango no puede superar 366 días.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_fin.required' => 'La fecha final es obligatoria.',
            'fecha_fin.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha de inicio.',
        ];
    }
}
