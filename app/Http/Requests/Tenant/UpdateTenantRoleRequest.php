<?php

namespace App\Http\Requests\Tenant;

use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UpdateTenantRoleRequest extends FormRequest
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
        /** @var Role|null $role */
        $role = $this->route('role');
        $isProtected = $role instanceof Role
            && TenantPermissionCatalog::isProtectedRole($role->name);

        $nameRules = $isProtected
            ? ['sometimes', 'string']
            : [
                'required',
                'string',
                'max:125',
                Rule::notIn(TenantPermissionCatalog::roleNames()),
                Rule::unique(config('permission.table_names.roles'), 'name')
                    ->where(fn ($query) => $query
                        ->where('guard_name', TenantPermissionCatalog::GUARD)
                        ->where('tenant_id', $tenantId))
                    ->ignore($role?->id),
            ];

        return [
            'name' => $nameRules,
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(TenantPermissionCatalog::all())],
        ];
    }
}
