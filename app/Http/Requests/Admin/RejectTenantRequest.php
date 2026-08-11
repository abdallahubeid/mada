<?php

namespace App\Http\Requests\Admin;

use App\Domain\Tenancy\Actions\RejectTenantAction;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a Super Admin's refusal of a tenant registration.
 *
 * The reason is required at the HTTP boundary as well as inside
 * {@see RejectTenantAction}: this rejects a blank
 * field with a form error the operator can fix, while the action's own guard
 * protects every other caller.
 */
class RejectTenantRequest extends FormRequest
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
            // Long enough to be an actual explanation. The applicant receives
            // this verbatim, and "no" on its own generates a support ticket.
            'rejection_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'يجب إدخال سبب الرفض — يُرسل نصه إلى مقدّم الطلب.',
            'rejection_reason.min' => 'يرجى توضيح سبب الرفض بما لا يقل عن 10 أحرف.',
            'rejection_reason.max' => 'سبب الرفض طويل جداً (الحد الأقصى 2000 حرف).',
        ];
    }
}
