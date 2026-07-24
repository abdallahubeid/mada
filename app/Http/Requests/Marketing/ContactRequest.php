<?php

namespace App\Http\Requests\Marketing;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the public contact / demo-request form (docs/MARKETING.md §2.1).
 */
class ContactRequest extends FormRequest
{
    /**
     * @var array<string, string>
     */
    public const SUBJECTS = [
        'demo' => 'طلب عرض توضيحي',
        'sales' => 'استفسار مبيعات',
        'support' => 'دعم فني',
        'partnership' => 'شراكة',
        'other' => 'أخرى',
    ];

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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'in:'.implode(',', array_keys(self::SUBJECTS))],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'يرجى إدخال الاسم الكامل.',
            'email.required' => 'يرجى إدخال البريد الإلكتروني.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'subject.required' => 'يرجى اختيار موضوع الرسالة.',
            'subject.in' => 'موضوع الرسالة غير صالح.',
            'message.required' => 'يرجى كتابة رسالتك.',
            'message.min' => 'يجب أن تحتوي الرسالة على 10 أحرف على الأقل.',
            'message.max' => 'الرسالة طويلة جداً (الحد الأقصى 5000 حرف).',
        ];
    }
}
