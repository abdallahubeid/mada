<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'quote' => ['required', 'string', 'max:2000'],
            'client_name' => ['required', 'string', 'max:120'],
            'client_role' => ['nullable', 'string', 'max:120'],
            'organization_name' => ['nullable', 'string', 'max:120'],
            'rate' => ['nullable', 'integer', 'min:1', 'max:5'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['sometimes', 'boolean'],
            'avatar' => ['nullable', 'file', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
            'logo' => ['nullable', 'file', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_published' => $this->boolean('is_published'),
            'sort_order' => $this->input('sort_order', 0),
        ]);
    }
}
