<?php

namespace App\Http\Requests\Tenant;

use App\Domain\Tenancy\Enums\AssetCondition;
use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hr.assets.manage') ?? false;
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
                    fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('status', EmployeeStatus::Active->value)
                        ->whereNull('deleted_at')
                ),
            ],
            'assigned_at' => ['nullable', 'date'],
            'condition_on_assign' => ['required', Rule::in(AssetCondition::values())],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
