<?php

namespace App\Http\Requests\Tenant;

use App\Domain\Tenancy\Models\TenantInvitation;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tenant.users.invite') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        return [
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')
                ),
                Rule::unique('tenant_invitations', 'email')->where(
                    fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('status', TenantInvitation::STATUS_PENDING)
                        ->where('expires_at', '>', now())
                ),
            ],
            'role' => [
                'required',
                'string',
                Rule::exists(config('permission.table_names.roles'), 'name')->where(
                    fn ($query) => $query
                        ->where('guard_name', TenantPermissionCatalog::GUARD)
                        ->where('tenant_id', $tenantId)
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'هذا البريد مسجّل بالفعل كعضو أو لديه دعوة معلّقة.',
            'role.exists' => 'الدور المحدد غير متاح في مؤسستك.',
        ];
    }
}
