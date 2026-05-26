<?php

namespace App\Http\Requests\Crm;

use App\Models\Crm\Orden_Compra;
use App\Models\Crm\OrdenDeTrabajo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrdenTrabajoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'observaciones' => ['required', 'string'],
        ];

        // 🔹 Solo validar sede_id si viene en la solicitud
        if ($this->filled('sede_id')) {
            $rules['sede_id'] = ['required', 'integer', Rule::exists('sedes', 'id')];
        }
      

       if ($this->debeValidarProducto()) {
    $rules['detalles'] = ['required', 'array', 'min:1'];
    $rules['detalles.*.product_id'] = [
        'nullable',
        'integer',
        Rule::exists('products', 'id')
    ];
}


        return $rules;
    }

    public function messages(): array
    {
        return [
            'observaciones.required' => 'El campo observaciones es obligatorio.',
            'observaciones.string' => 'El campo observaciones debe ser una cadena de texto.',
            'sede_id.required' => 'El campo sede es obligatorio.',
            'sede_id.integer' => 'El campo sede debe ser un número entero.',
            'sede_id.exists' => 'La sede seleccionada no existe en el sistema.',
           'detalles.required' => 'Debe agregar al menos un ítem.',

'detalles.*.product_id.exists' => 'El producto seleccionado no existe.',

        ];
    }

    /**
     * 🔸 Prepara los datos antes de la validación.
     * Si no viene "sede_id" en el request, lo toma del usuario autenticado.
     */
protected function prepareForValidation()
{
    $ordenCompraId = $this->route('id');

    if (!$this->filled('sede_id') && $ordenCompraId) {
        $ordenCompra = Orden_Compra::find($ordenCompraId);

        if ($ordenCompra && $ordenCompra->sede_id) {
            $this->merge([
                'sede_id' => $ordenCompra->sede_id,
            ]);
        }
    }
}
    private function debeValidarProducto(): bool
    {
       $ordenCompraId = $this->route('id');
       if(!$ordenCompraId) {
            return true; // Si no hay orden de compra, siempre validar
       }

       return !OrdenDeTrabajo::where('orden_compra_id', $ordenCompraId)->exists();  

    }
}

