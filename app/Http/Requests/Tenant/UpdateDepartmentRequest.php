<?php

namespace App\Http\Requests\Tenant;

use App\Domain\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hr.departments.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->getTenantId();
        $departmentId = $this->route('department')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('departments', 'code')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at'))
                    ->ignore($departmentId),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::notIn([$departmentId]),
                Rule::exists('departments', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')
                ),
            ],
            'department_head_id' => [
                'nullable',
                'integer',
                Rule::exists('employees', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')
                ),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['code', 'parent_id', 'department_head_id'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }
}
