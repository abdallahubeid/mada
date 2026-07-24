<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantMarketingRequest extends FormRequest
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
            'show_on_marketing' => ['sometimes', 'boolean'],
            'marketing_logo' => ['nullable', 'file', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'remove_logo' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'show_on_marketing' => $this->boolean('show_on_marketing'),
            'remove_logo' => $this->boolean('remove_logo'),
        ]);
    }
}
