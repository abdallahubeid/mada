<?php

namespace App\Http\Requests\Tenant;

use App\Domain\Tenancy\Enums\EmploymentType;
use App\Domain\Tenancy\Enums\JobPostingStatus;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobPostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hr.jobs.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['department_id', 'location', 'salary_range', 'requirements'] as $field) {
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
            'title' => ['required', 'string', 'max:255'],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')
                ),
            ],
            'employment_type' => ['required', Rule::in(EmploymentType::values())],
            'location' => ['nullable', 'string', 'max:255'],
            'salary_range' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:20000'],
            'requirements' => ['nullable', 'string', 'max:20000'],
            'status' => ['required', Rule::in(JobPostingStatus::values())],
        ];
    }
}
