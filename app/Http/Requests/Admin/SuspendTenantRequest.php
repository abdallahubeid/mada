<?php

namespace App\Http\Requests\Admin;

use App\Domain\Tenancy\Actions\SuspendTenantAction;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a Super Admin's suspension of a live tenant.
 *
 * Mirrors {@see RejectTenantRequest}: the reason is enforced here so the
 * operator gets a fixable form error, and again inside
 * {@see SuspendTenantAction} so every other caller is covered.
 */
class SuspendTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tenants.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // The owner receives this verbatim, and support reads it back
            // months later when the customer asks why they were locked out.
            'suspension_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'suspension_reason.required' => 'يجب إدخال سبب الإيقاف — يُرسل نصه إلى مالك الحساب.',
            'suspension_reason.min' => 'يرجى توضيح سبب الإيقاف بما لا يقل عن 10 أحرف.',
            'suspension_reason.max' => 'سبب الإيقاف طويل جداً (الحد الأقصى 2000 حرف).',
        ];
    }
}
