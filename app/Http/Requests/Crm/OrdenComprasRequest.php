<?php

namespace App\Http\Requests\Crm;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class OrdenComprasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'fecha_entrega' => 'required',
            'cliente_id' => 'required',
            'ubicacion_entrega' => 'required',
            'observaciones' => 'required',
        ];

        // Validaciones condicionales para detalles
        $rules['detalles'] = 'nullable|array|min:1';
        $rules['detalles.*.largo_cm'] = 'required_with:detalles';
        $rules['detalles.*.ancho_cm'] = 'required_with:detalles';
        $rules['detalles.*.calibre'] = 'required_with:detalles';
        $rules['detalles.*.cantidad'] = 'required_with:detalles';
        $rules['detalles.*.cantidad_enviada'] = 'nullable_with:detalles';
        $rules['detalles.*.faltantes'] = 'nullable_with:detalles';
        $rules['detalles.*.valor_unitario'] = 'required_with:detalles|numeric|regex:/^\d+(\.\d{1,2})?$/';

        $rules['detalles.*.peso_bolsa'] = 'required_with:detalles';
        $rules['detalles.*.numero_bolsas'] = 'required_with:detalles|integer';
        $rules['detalles.*.cliente_clb'] = 'required_with:detalles';
        $rules['detalles.*.cantidad_requerida_kg'] = 'required_with:detalles';
        $rules['detalles.*.descripcion'] = 'required_with:detalles';
        $rules['detalles.*.valor_total'] = 'required_with:detalles|numeric|regex:/^\d+(\.\d{1,2})?$/';



        return $rules;
    }


    public function withValidator($validator)
{
    $validator->after(function ($validator) {
        $fechaEntrega = $this->input('fecha_entrega');
        if ($fechaEntrega) {
            $fechaMinima = Carbon::now();
            $diasHabiles = 0;
            while ($diasHabiles < 5) {
                $fechaMinima->addDay();
                if (!in_array($fechaMinima->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                    $diasHabiles++;
                }
            }
            if (Carbon::parse($fechaEntrega)->lt($fechaMinima)) {
                $validator->errors()->add('fecha_entrega', 'La fecha de entrega debe ser al menos 5 días hábiles después de hoy (sin contar sábados ni domingos).');
            }
        }
    });
}

    public function messages(): array
    {
        return [
            'fecha_entrega.required' => 'La fecha de entrega es obligatoria',
            'cliente_id.required' => 'El cliente es obligatorio',
            'ubicacion_entrega.required' => 'La ubicación de entrega es obligatoria',
            'observaciones.required' => 'Las observaciones son obligatorias',
            

            // Mensajes de error para detalles
            'detalles.min' => 'Debes ingresar mínimo un elemento',
            'detalles.*.largo_cm.required_with' => 'El largo es obligatorio',
            
            'detalles.*.ancho_cm.required_with' => 'El ancho es obligatorio',
            'detalles.*.calibre.required_with' => 'El calibre es obligatorio',
            'detalles.*.cantidad.required_with' => 'La cantidad es obligatoria',
            'detalles.*.valor_unitario.required_with' => 'El valor unitario es obligatorio',
            'detalles.*.peso_bolsa.required_with' => 'El peso de la bolsa es obligatorio',
            'detalles.*.numero_bolsas.required_with' => 'El número de bolsas es obligatorio',
            'detalles.*.numero_bolsas.integer' => 'El número de bolsas debe ser un número entero',
            'detalles.*.cliente_clb.required_with' => 'El cliente es obligatorio',
            'detalles.*.cantidad_requerida_kg.required_with' => 'La cantidad requerida en kg es obligatoria',
            'detalles.*.unidad_empaque.required_with' => 'La unidad de empaque es obligatoria',
            'detalles.*.descripcion.required_with' => 'La descripcion es Obligatoria',
            'detalles.*.valor_total.required_with' => 'El valor total es obligatorio',

          
        ];
    }
}