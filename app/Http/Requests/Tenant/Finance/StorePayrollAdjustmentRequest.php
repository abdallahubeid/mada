<?php

namespace App\Http\Requests\Tenant\Finance;

use App\Domain\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.payroll.prepare') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        return [
            'original_payslip_id' => [
                'required', 'integer',
                Rule::exists('payslips', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')
                ),
            ],
            // Signed MAJOR units: negative claws back, positive pays extra.
            // Converted to minor units in the controller (ADR-20).
            'amount' => ['required', 'numeric', 'not_in:0', 'min:-99999999', 'max:99999999'],
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.not_in' => 'قيمة التسوية لا يمكن أن تكون صفراً.',
            'reason.required' => 'يجب ذكر سبب التسوية — القيد سجل مالي دائم.',
        ];
    }
}
