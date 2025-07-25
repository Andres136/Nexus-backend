<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class RequestMeta extends FormRequest
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
            'anio' => 'required|integer',
            'mes' => 'required|integer',
        ];

    }
    /**
     * Get the validation messages.
     */
    public function messages(): array
    {
        return [
            'anio.required' => 'El año es obligatorio.',
            'anio.integer' => 'El año debe ser un número entero.',
            'mes.required' => 'El mes es obligatorio.',
            'mes.integer' => 'El mes debe ser un número entero.',
        ];
    
    
    }
}
