<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class StoreGestionCarteraRequest extends FormRequest
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
        'registros' => 'required|array|min:1',
        'registros.*.empresa_id' => 'required|exists:empresas,id',

        'registros.*.numero_factura' => 'required|string|max:255',
        'registros.*.user_comercial_id' => 'required|exists:users,id',
        'registros.*.cliente_id' => 'required|exists:clientes,id',
      
        'registros.*.valor_total' => 'required|numeric',
    

        'registros.*.saldo_pendiente' => 'nullable|numeric',
        'registros.*.fecha_vencimiento' => 'nullable|date',
        'registros.*.observaciones' => 'nullable|string',
        'registros.*.fecha_factura' => 'required|date',

        'registros.*.estado' => 'nullable|string|in:pendiente,completado',
        'registros.*.dias_credito' => 'required|integer',

        'registros.*.base' => 'nullable|numeric',
        'registros.*.iva' => 'nullable|numeric',
        'registros.*.rete_renta' => 'nullable|numeric',
        'registros.*.rete_ica' => 'nullable|numeric'
    ];
}

    public function messages()
    {
        return [
            'registros.required' => 'Se requiere al menos un registro de gestión de cartera.',
            'registros.array' => 'Los registros deben ser un arreglo.',
            'registros.*.empresa_id.required' => 'El campo de la empresa es obligatorio para cada registro.',
            'registros.*.empresa_id.exists' => 'La empresa no existe en la base de datos.',
            'registros.*.numero_factura.required' => 'El número de factura es obligatorio para cada registro.',
            'registros.*.user_comercial_id.required' => 'El campo del usuario comercial es obligatorio para cada registro.',
            'registros.*.user_comercial_id.exists' => 'El usuario comercial no existe en la base de datos.',
            'registros.*.cliente_id.required' => 'El campo del cliente es obligatorio para cada registro.',
            'registros.*.cliente_id.exists' => 'El cliente no existe en la base de datos.',
            'registros.*.valor_total.required' => 'El valor total es obligatorio para cada registro.',
            'registros.*.valor_total.numeric' => 'El valor total debe ser un número.',
            'registros.*.saldo_pendiente.numeric' => 'El saldo pendiente debe ser un número.',
            'registros.*.fecha_vencimiento.date' => 'La fecha de vencimiento debe ser una fecha válida.',
            'registros.*.observaciones.string' => 'Las observaciones deben ser una cadena de texto.',
            'registros.*.fecha_factura.required' => 'La fecha de factura es obligatoria para cada registro.',
            'registros.*.fecha_factura.date' => 'La fecha de factura debe ser una fecha válida.',
            'registros.*.estado.string' => 'El estado debe ser una cadena de texto.',
            'registros.*.estado.in' => 'El estado debe ser "pendiente" o "completado".',
            'registros.*.dias_credito.required' => 'Los días de crédito son obligatorios para cada registro.',
            'registros.*.dias_credito.integer' => 'Los días de crédito deben ser un número entero.',

            'registros.*.base.numeric' => 'La base debe ser un número.',
            'registros.*.iva.numeric' => 'El IVA debe ser un número.',
            'registros.*.rete_renta.numeric' => 'La retención de renta debe ser un número.',
            'registros.*.rete_ica.numeric' => 'La retención de ICA debe ser un número.',
        ];

    }
}
