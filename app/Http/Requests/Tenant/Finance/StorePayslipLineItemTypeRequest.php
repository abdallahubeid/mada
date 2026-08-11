<?php

namespace App\Http\Requests\Tenant\Finance;

use App\Domain\Finance\Enums\PayslipLineItemKind;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayslipLineItemTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.line_item_types.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('code') === '') {
            $this->merge(['code' => null]);
        }

        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_taxable' => $this->boolean('is_taxable'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable', 'string', 'max:32',
                Rule::unique('payslip_line_item_types', 'code')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'kind' => ['required', Rule::in(PayslipLineItemKind::values())],
            // Submitted as a positive magnitude; the controller applies the
            // sign from `kind` (ADR-20).
            'default_amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'is_active' => ['boolean'],
            'is_taxable' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
