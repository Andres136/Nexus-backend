<?php

namespace App\Http\Requests\Traslados;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrasladoRequest extends FormRequest
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
        
           'bodega_origen_id'=>'required|exists:bodegas,id',
           'bodega_destino_id'=>'required|exists:bodegas,id|different:bodega_origen_id',
    
           'cantidad_total'=>'required|numeric|min:0',
      //  'usuario_aprobador_id'=>'required|exists:users,id', //DEbe de venir del responsable
        
           'fecha_despacho'=>'nullable|date',
           'fecha_recepcion'=>'nullable|date',
           'observaciones'=>'nullable|string|max:255',

           //DETALLES OBLIGATORIOS
           'detalles' => 'required|array|min:1',
           'detalles.*.producto_id'=>'required|exists:products,id',
           'detalles.*.cantidad'=>'required|numeric|min:1',


        ];
    }

    public function messages(): array
    {
        return [
            'bodega_origen_id.required' => 'La bodega de origen es obligatoria.',
            'bodega_destino_id.required' => 'La bodega de destino es obligatoria.',
            'user_id.required' => 'El usuario es obligatorio.',
            'peso_total.required' => 'El peso total es obligatorio.',
            'usuario_aprobador_id.required' => 'El usuario aprobador es obligatorio.',
            'estado.required' => 'El estado es obligatorio.',
            'detalles.required' => 'Los detalles son obligatorios.',
            'detalles.*.producto_id.required' => 'El producto es obligatorio.',
            'detalles.*.cantidad.required' => 'La cantidad es obligatoria.',
            'detalles.*.peso.required' => 'El peso es obligatorio.',
            'detalles.*.precio_unitario.required' => 'El precio unitario es obligatorio.',
        ];
    }
}
