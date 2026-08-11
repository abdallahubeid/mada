<?php

namespace App\Http\Requests\Tenant;

use App\Domain\Tenancy\Enums\ContractStatus;
use App\Domain\Tenancy\Enums\ContractType;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hr.contracts.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['end_date', 'probation_end_date', 'notes'] as $field) {
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
                'required',
                'integer',
                Rule::exists('employees', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')
                ),
            ],
            'contract_type' => ['required', Rule::in(ContractType::values())],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'probation_end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(ContractStatus::values())],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
