<?php

namespace App\Http\Requests\Nomina;

use App\Models\Nomina\KioskoDevice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKioskoDeviceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $uuid = $this->route('kiosko_device');
        $deviceId = KioskoDevice::where('uuid', $uuid)->value('id');

        return [
            'sede_id'           => 'sometimes|exists:sedes,id',
            'name'              => 'sometimes|string|max:45',
            'code'              => ['sometimes', 'string', 'max:45', Rule::unique('kiosko_devices', 'code')->ignore($deviceId)],
            'ip_adres'          => 'sometimes|string|max:45',
            'descripcion'       => 'nullable|string|max:255',
            'bodega_id'         => 'sometimes|exists:bodegas,id',
            'tipo_registros_id' => 'sometimes|exists:tipo_registros,id',
        ];
    }

    public function messages(): array
    {
        return [
            'sede_id.exists'           => 'La sede no existe.',
            'code.unique'              => 'Este código ya está en uso.',
            'bodega_id.exists'         => 'La bodega no existe.',
            'tipo_registros_id.exists' => 'El tipo de registro no existe.',
        ];
    }
}
