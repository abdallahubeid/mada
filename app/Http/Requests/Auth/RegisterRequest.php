<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Validates the SaaS multi-step registration wizard (docs/USER_JOURNEYS.md
 * — Onboarding). All three steps are submitted together in a single POST;
 * client-side (Alpine.js) step navigation in resources/views/auth/register.blade.php
 * is presentational only — this request is the single source of truth for
 * what is actually required.
 */
class RegisterRequest extends FormRequest
{
    /**
     * Industry options for Step 2 (Organization Details).
     *
     * @var array<string, string>
     */
    public const INDUSTRIES = [
        'technology' => 'تقنية المعلومات',
        'retail' => 'التجارة والتجزئة',
        'manufacturing' => 'الصناعة والتصنيع',
        'finance' => 'الخدمات المالية',
        'healthcare' => 'الرعاية الصحية',
        'education' => 'التعليم',
        'real_estate' => 'العقارات والإنشاءات',
        'other' => 'أخرى',
    ];

    /**
     * Team-size brackets for Step 2 (Organization Details).
     *
     * @var array<string, string>
     */
    public const TEAM_SIZES = [
        '1-10' => '١ – ١٠ موظفين',
        '11-50' => '١١ – ٥٠ موظف',
        '51-200' => '٥١ – ٢٠٠ موظف',
        '201-500' => '٢٠١ – ٥٠٠ موظف',
        '500+' => 'أكثر من ٥٠٠ موظف',
    ];

    /**
     * Plan tiers for Step 3 (Plan Selection & Review) — mirrors the public
     * pricing table on resources/views/landing.blade.php.
     *
     * @var array<string, array{label: string, tagline: string, price: string}>
     */
    public const PLANS = [
        'startup' => ['label' => 'Startup', 'tagline' => 'للشركات الناشئة والفرق الصغيرة', 'price' => '49 $ / شهرياً'],
        'growth' => ['label' => 'Growth', 'tagline' => 'للمؤسسات المتوسطة سريعة النمو', 'price' => '129 $ / شهرياً'],
        'enterprise' => ['label' => 'Enterprise', 'tagline' => 'للمؤسسات الكبيرة ومتطلبات مخصصة', 'price' => 'تواصل مع المبيعات'],
    ];

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'company_name' => ['required', 'string', 'max:255'],
            'company_slug' => [
                'required',
                'string',
                'min:3',
                'max:63',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('tenants', 'slug'),
            ],
            'industry' => ['required', 'string', Rule::in(array_keys(self::INDUSTRIES))],
            'team_size' => ['required', 'string', Rule::in(array_keys(self::TEAM_SIZES))],
            'plan' => ['required', 'string', Rule::in(array_keys(self::PLANS))],
            'terms' => ['accepted'],
        ];
    }

    /**
     * Get custom messages for validator errors, in Arabic to match the
     * fully Arabic registration UI.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'يرجى إدخال الاسم الكامل.',
            'email.required' => 'يرجى إدخال البريد الإلكتروني للعمل.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique' => 'هذا البريد الإلكتروني مسجل بالفعل.',
            'password.required' => 'يرجى إدخال كلمة المرور.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
            'company_name.required' => 'يرجى إدخال اسم المؤسسة.',
            'company_slug.required' => 'يرجى إدخال المعرّف الفريد للمؤسسة.',
            'company_slug.regex' => 'يجب أن يحتوي المعرّف على أحرف إنجليزية صغيرة وأرقام وشرطات فقط.',
            'company_slug.unique' => 'هذا المعرّف مستخدم من مؤسسة أخرى، جرّب معرّفاً آخر.',
            'company_slug.min' => 'يجب أن يتكون المعرّف من 3 أحرف على الأقل.',
            'industry.required' => 'يرجى اختيار قطاع النشاط.',
            'team_size.required' => 'يرجى اختيار حجم فريق العمل.',
            'plan.required' => 'يرجى اختيار خطة الاشتراك.',
            'terms.accepted' => 'يجب الموافقة على الشروط والأحكام لإنشاء الحساب.',
        ];
    }
}
