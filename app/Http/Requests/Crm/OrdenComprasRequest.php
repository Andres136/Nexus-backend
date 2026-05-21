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
            'empresa_id' => 'required|exists:empresas,id',
            'cliente_documento' =>  'required|mimes:pdf,doc,docx,xls,xlsx|max:10240', // 10MB in KB
        ];

        // Validaciones condicionales para detalles
        $rules['detalles'] = 'nullable|array|min:1';
        $rules['detalles.*.product_id'] = 'sometimes|nullable|exists:products,id';
        $rules['detalles.*.largo_cm'] = 'nullable:detalles|numeric';
        $rules['detalles.*.ancho_cm'] = 'nullable:detalles|numeric';
        $rules['detalles.*.calibre'] = 'required_with:detalles';
     $rules['detalles.*.cantidad'] = 'required_with:detalles|integer|min:1';
        $rules['detalles.*.cantidad_enviada'] = 'nullable_with:detalles';
        $rules['detalles.*.faltantes'] = 'nullable_with:detalles';
      $rules['detalles.*.valor_unitario'] = 'required_with:detalles|numeric|min:0';

        $rules['detalles.*.peso_bolsa'] = 'required_with:detalles';
        $rules['detalles.*.numero_bolsas'] = 'required_with:detalles|integer';
        $rules['detalles.*.cliente_clb'] = 'required_with:detalles';
        $rules['detalles.*.cantidad_requerida_kg'] = 'required_with:detalles';
        $rules['detalles.*.descripcion'] = 'required_with:detalles';
        $rules['detalles.*.tipo_embalaje'] = 'required_with:detalles';
        $rules['detalles.*.valor_total'] = 'required_with:detalles|numeric|min:0';



        return $rules;
    }




    public function messages(): array
    {
        return [
            'fecha_entrega.required' => 'La fecha de entrega es obligatoria',
            'cliente_id.required' => 'El cliente es obligatorio',
            'ubicacion_entrega.required' => 'La ubicación de entrega es obligatoria',
            'observaciones.required' => 'Las observaciones son obligatorias',
            'empresa_id.required' => 'La empresa es obligatoria',
            'empresa_id.exists' => 'La empresa seleccionada no es válida',
            'cliente_documento.required' => 'El documento del cliente es obligatorio',
            'cliente_documento.mimes' => 'El documento del cliente debe ser un archivo de tipo: pdf, doc, docx, xls, xlsx',
            'cliente_documento.max' => 'El documento del cliente no debe superar los 10MB',
            

            // Mensajes de error para detalles
    
            'detalles.min' => 'Debes ingresar mínimo un elemento',
            'detalles.*.product_id.exists' => 'El producto seleccionado no es válido',
            'detalles.*.largo_cm.numeric' => 'El largo debe ser un número válido',
            'detalles.*.ancho_cm.numeric' => 'El ancho debe ser un número válido',
            'detalles.*.calibre.required_with' => 'El calibre es obligatorio',
            'detalles.*.cantidad.required_with' => 'La cantidad es obligatoria',
            'detalles.*.cantidad.integer' => 'La cantidad debe ser un número entero',
            'detalles.*.cantidad.min' => 'La cantidad debe ser al menos 1',
            'detalles.*.valor_unitario.required_with' => 'El valor unitario es obligatorio',
            'detalles.*.valor_unitario.numeric' => 'El valor unitario debe ser un número válido',
            'detalles.*.valor_unitario.min' => 'El valor unitario no puede ser negativo',
            'detalles.*.cantidad_enviada.nullable_with' => 'La cantidad enviada debe ser un número válido',
            'detalles.*.faltantes.nullable_with' => 'Los faltantes deben ser un número válido',
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