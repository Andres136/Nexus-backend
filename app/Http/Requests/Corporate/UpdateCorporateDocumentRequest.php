<?php

namespace App\Http\Requests\Corporate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCorporateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'features' => $this->decodeList('features'),
            'benefits' => $this->decodeList('benefits'),
            'is_active' => filter_var($this->input('is_active', true), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
            'is_public' => filter_var($this->input('is_public', true), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
        ]);
    }

    public function rules(): array
    {
        $documentId = $this->route('corporateDocument');

        return [
            'slug' => [
                'nullable',
                'string',
                'max:120',
                Rule::unique('corporate_documents', 'slug')->ignore($documentId),
            ],
            'title' => 'required|string|max:180',
            'subtitle' => 'required|string|max:220',
            'description' => 'required|string',
            'long_description' => 'nullable|string',
            'icon' => 'nullable|string|max:60',
            'pages' => 'nullable|integer|min:1',
            'last_update' => 'nullable|date',
            'category' => 'required|string|max:80',
            'theme' => 'required|string|max:60',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'file' => 'nullable|file|mimes:pdf|max:30720',
            'features' => 'nullable|array',
            'features.*' => 'nullable|string|max:255',
            'benefits' => 'nullable|array',
            'benefits.*' => 'nullable|string|max:255',
        ];
    }

    private function decodeList(string $key): mixed
    {
        $value = $this->input($key);
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        return $value;
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'The slug must be unique.',
            'title.required' => 'The title field is required.',
            'subtitle.required' => 'The subtitle field is required.',
            'description.required' => 'The description field is required.',
        ];
    }
}
