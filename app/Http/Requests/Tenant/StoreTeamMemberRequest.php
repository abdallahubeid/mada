<?php

namespace App\Http\Requests\Tenant;

use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tenant.users.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'auto_generate_password' => $this->boolean('auto_generate_password'),
            'email' => strtolower((string) $this->input('email')),
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
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at')),
            ],
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where(fn ($query) => $query
                    ->where('guard_name', TenantPermissionCatalog::GUARD)
                    ->where('tenant_id', $tenantId)),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(TenantPermissionCatalog::all())],
            'auto_generate_password' => ['required', 'boolean'],
            'password' => [
                Rule::requiredIf(! $this->boolean('auto_generate_password')),
                'nullable',
                'confirmed',
                Password::defaults(),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required' => 'أدخل كلمة المرور أو فعّل التوليد التلقائي.',
        ];
    }
}
