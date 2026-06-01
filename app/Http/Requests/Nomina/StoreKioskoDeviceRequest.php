<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StoreKioskoDeviceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'sede_id'           => 'required|exists:sedes,id',
            'name'              => 'required|string|max:45',
         
            'ip_adres'          => 'required|string|max:45',
            'descripcion'       => 'nullable|string|max:255',
            'bodega_id'         => 'required|exists:bodegas,id',
            'tipo_registros_id' => 'required|exists:tipo_registros,id',
        ];
    }

    public function messages(): array
    {
        return [
            'sede_id.required'           => 'La sede es obligatoria.',
            'sede_id.exists'             => 'La sede no existe.',
            'name.required'              => 'El nombre es obligatorio.',
            'name.max'                   => 'El nombre no puede superar 45 caracteres.',
            'code.required'              => 'El código es obligatorio.',
            'code.unique'                => 'Este código ya está en uso.',
            'ip_adres.required'          => 'La dirección IP es obligatoria.',
            'bodega_id.required'         => 'La bodega es obligatoria.',
            'bodega_id.exists'           => 'La bodega no existe.',
            'tipo_registros_id.required' => 'El tipo de registro es obligatorio.',
            'tipo_registros_id.exists'   => 'El tipo de registro no existe.',
        ];
    }
}
