<?php

namespace App\Http\Requests\Hseq;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreClausulaIsoRequest extends FormRequest
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
            'norma_iso_id' => 'required_without:normas|nullable|exists:normas_iso,id',
            'codigo' => [
                'required_without:normas',
                'nullable',
                'string',
                'max:255',
                Rule::unique('clausulas_iso', 'codigo')->where(fn ($query) => $query->where('norma_iso_id', $this->norma_iso_id)),
            ],
            'normas' => 'required_without:norma_iso_id|nullable|array|min:1',
            'normas.*.norma_iso_id' => 'required|distinct|exists:normas_iso,id',
            'normas.*.codigo' => 'required|string|max:255',
            'descripcion' => 'required|string|max:255',
            'activa' => 'nullable|boolean',
        ];
    }

    public function messages()
    {
        return [
            'norma_iso_id.required' => 'La norma ISO es obligatoria.',
            'norma_iso_id.exists' => 'La norma ISO especificada no existe.',
            'normas.required_without' => 'Debes seleccionar al menos una norma ISO.',
            'normas.min' => 'Debes seleccionar al menos una norma ISO.',
            'normas.*.norma_iso_id.distinct' => 'No puedes seleccionar la misma norma más de una vez.',
            'normas.*.norma_iso_id.exists' => 'Una de las normas ISO seleccionadas no existe.',
            'normas.*.codigo.required' => 'El código es obligatorio para cada norma seleccionada.',
            'codigo.required' => 'El código de la cláusula es obligatorio.',
            'codigo.string' => 'El código de la cláusula debe ser una cadena de texto.',
            'codigo.max' => 'El código de la cláusula no puede exceder los 255 caracteres.',
            'codigo.unique' => 'Ya existe una cláusula con ese código para esta norma.',
            'descripcion.required' => 'La descripción de la cláusula es obligatoria.',
            'descripcion.string' => 'La descripción de la cláusula debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción de la cláusula no puede exceder los 255 caracteres.',
            'activa.boolean' => 'El campo activa debe ser verdadero o falso.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                foreach ($this->input('normas', []) as $index => $norma) {
                    $existe = \DB::table('clausulas_iso')
                        ->where('norma_iso_id', $norma['norma_iso_id'] ?? null)
                        ->where('codigo', $norma['codigo'] ?? null)
                        ->exists();

                    if ($existe) {
                        $validator->errors()->add(
                            "normas.{$index}.codigo",
                            'Ya existe una cláusula con ese código para esta norma.'
                        );
                    }
                }
            },
        ];
    }
}
