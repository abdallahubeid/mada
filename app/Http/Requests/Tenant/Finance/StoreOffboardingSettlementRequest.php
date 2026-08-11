<?php

namespace App\Http\Requests\Tenant\Finance;

use App\Domain\Finance\Enums\OffboardingReason;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOffboardingSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.offboarding.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['loan_deduction', 'other_deduction', 'notes'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        return [
            'employee_id' => [
                'required', 'integer',
                Rule::exists('employees', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')
                ),
            ],
            'last_working_day' => ['required', 'date'],
            'reason' => ['required', Rule::in(OffboardingReason::values())],

            /*
             * Loan and other deductions are MANUAL inputs, entered as positive
             * magnitudes in major units. There is no loans table in the system,
             * so an outstanding advance cannot be derived — it has to be typed
             * in by whoever prepares the settlement. Flagged as a spec gap.
             */
            'loan_deduction' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'other_deduction' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
