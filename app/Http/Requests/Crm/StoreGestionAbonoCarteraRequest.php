<?php

namespace App\Http\Requests\Crm;

use App\Models\Crm\GestionCartera;
use Illuminate\Foundation\Http\FormRequest;

class StoreGestionAbonoCarteraRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'gestion_cartera_id' => 'required|exists:gestion_cartera,id',
            'valor_pago' => 'required|numeric',
            'fecha_pago' => 'required|date'
        ];
    }

    public function withValidator($validator)
{
    $validator->after(function ($validator) {

        $cartera = GestionCartera::find($this->gestion_cartera_id);

        if (!$cartera) return;

        if ($this->valor_pago > $cartera->saldo_pendiente) {
            $validator->errors()->add(
                'valor_pago',
                'El abono no puede ser mayor al saldo pendiente.'
            );
        }
    });
}

    public function messages()
    {
        return [
            'gestion_cartera_id.required' => 'El ID de la gestión de cartera es obligatorio.',
            'gestion_cartera_id.exists' => 'El ID de la gestión de cartera no existe en la base de datos.',
            'valor_pago.required' => 'El valor del pago es obligatorio.',
            'valor_pago.numeric' => 'El valor del pago debe ser un número.',
            'fecha_pago.required' => 'La fecha del pago es obligatoria.',
            'fecha_pago.date' => 'La fecha del pago debe ser una fecha válida.'
        ];
    }
}
