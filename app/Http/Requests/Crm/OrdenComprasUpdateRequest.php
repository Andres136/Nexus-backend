<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class OrdenComprasUpdateRequest extends FormRequest
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
        $rules = [
            'fecha_entrega' => 'required',
            'cliente_id' => 'required',
            'ubicacion_entrega' => 'required',
            'observaciones' => 'required',
            'empresa_id' => 'required|exists:empresas,id',
            'cliente_documento' => 'sometimes|file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
        ];

        // Validaciones condicionales para detalles
        $rules['detalles'] = 'nullable|array|min:1';
        $rules['detalles.*.id'] = 'sometimes|nullable|exists:orden__compra__detalles,id';
        $rules['detalles.*.product_id'] = 'sometimes|nullable|exists:products,id';
        $rules['detalles.*.largo_cm'] = 'required_with:detalles';
        $rules['detalles.*.ancho_cm'] = 'required_with:detalles';
        $rules['detalles.*.calibre'] = 'required_with:detalles';
        $rules['detalles.*.cantidad'] = 'required_with:detalles';
        $rules['detalles.*.cantidad_enviada'] = 'nullable';
        $rules['detalles.*.faltantes'] = 'nullable';
        $rules['detalles.*.valor_unitario'] = 'required_with:detalles|numeric|regex:/^\d+(\.\d{1,2})?$/';

        $rules['detalles.*.peso_bolsa'] = 'required_with:detalles';
        $rules['detalles.*.numero_bolsas'] = 'required_with:detalles|integer';
        $rules['detalles.*.cliente_clb'] = 'required_with:detalles';
        $rules['detalles.*.cantidad_requerida_kg'] = 'required_with:detalles';
        $rules['detalles.*.descripcion'] = 'required_with:detalles';
        $rules['detalles.*.tipo_embalaje'] = 'sometimes|nullable|string';
        $rules['detalles.*.codigo_embalaje'] = 'sometimes|nullable|string';
        $rules['detalles.*.valor_total'] = 'required_with:detalles|numeric|regex:/^\d+(\.\d{1,2})?$/';



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
            'cliente_documento.mimes' => 'El documento del cliente debe ser un archivo de tipo: pdf, doc, docx, xls, xlsx',
            'cliente_documento.max' => 'El documento del cliente no debe superar los 10MB',
            

            // Mensajes de error para detalles
            'detalles.min' => 'Debes ingresar mínimo un elemento',
            'detalles.*.product_id.exists' => 'El producto seleccionado no es válido',
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

// Route::get('ordenes-compra-facturar', [OrdenCompraController::class, 'ordenesFacturar']);
