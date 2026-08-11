<?php

namespace App\Http\Requests\Tenant;

use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hr.employees.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'create_user_account' => $this->boolean('create_user_account'),
            'auto_generate_password' => $this->boolean('auto_generate_password'),
            'remove_avatar' => $this->boolean('remove_avatar'),
            'remove_cv' => $this->boolean('remove_cv'),
        ]);

        foreach (['department_id', 'manager_id', 'national_id', 'phone', 'address'] as $field) {
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
        /** @var Employee $employee */
        $employee = $this->route('employee');

        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:2000'],
            'job_title' => ['required', 'string', 'max:255'],
            'joining_date' => ['required', 'date'],
            'status' => ['required', Rule::in(EmployeeStatus::values())],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')
                ),
            ],
            'manager_id' => [
                'nullable',
                'integer',
                Rule::notIn([$employee->id]),
                Rule::exists('employees', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')
                ),
            ],
            'avatar' => ['nullable', 'image', 'max:4096'],
            'cv' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'remove_avatar' => ['required', 'boolean'],
            'remove_cv' => ['required', 'boolean'],
            'create_user_account' => ['required', 'boolean'],
            'email' => [
                Rule::requiredIf($this->boolean('create_user_account') && $employee->user_id === null),
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'password' => [
                Rule::requiredIf(
                    $this->boolean('create_user_account')
                    && $employee->user_id === null
                    && ! $this->boolean('auto_generate_password')
                ),
                'nullable',
                'confirmed',
                Password::defaults(),
            ],
            'auto_generate_password' => ['sometimes', 'boolean'],
        ];
    }
}
