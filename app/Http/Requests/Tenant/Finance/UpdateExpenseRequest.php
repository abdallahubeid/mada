<?php

namespace App\Http\Requests\Tenant\Finance;

use App\Domain\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.expenses.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['description', 'employee_id', 'expense_category_id'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }

        $this->merge(['is_claimable' => $this->boolean('is_claimable')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'is_claimable' => ['boolean'],
            'expense_category_id' => [
                'nullable', 'integer',
                Rule::exists('expense_categories', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')
                ),
            ],
            'employee_id' => [
                'nullable', 'integer',
                Rule::exists('employees', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')
                ),
            ],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
