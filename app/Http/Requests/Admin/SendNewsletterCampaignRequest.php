<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendNewsletterCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:3', 'max:50000'],
            'exclude_ids' => ['nullable', 'array'],
            'exclude_ids.*' => ['integer', 'exists:newsletter_subscribers,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject.required' => 'يرجى إدخال موضوع الرسالة.',
            'body.required' => 'يرجى إدخال محتوى الرسالة.',
        ];
    }
}
