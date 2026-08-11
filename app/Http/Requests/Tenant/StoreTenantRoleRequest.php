<?php

namespace App\Http\Requests\Tenant;

use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tenant.roles.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        return [
            'name' => [
                'required',
                'string',
                'max:125',
                Rule::notIn(TenantPermissionCatalog::roleNames()),
                Rule::unique(config('permission.table_names.roles'), 'name')
                    ->where(fn ($query) => $query
                        ->where('guard_name', TenantPermissionCatalog::GUARD)
                        ->where('tenant_id', $tenantId)),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(TenantPermissionCatalog::all())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.not_in' => 'لا يمكن إنشاء دور بنفس اسم الأدوار النظامية.',
            'name.unique' => 'هذا الاسم مستخدم لدور آخر في مؤسستك.',
        ];
    }
}
